<?php

namespace NineteenNinetyFour\PromoBar;

use Carbon\CarbonImmutable;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use InvalidArgumentException;
use NineteenNinetyFour\PromoBar\Blueprints\SettingsBlueprint;
use NineteenNinetyFour\PromoBar\Caching\Fingerprint;
use NineteenNinetyFour\PromoBar\Caching\Invalidator;
use NineteenNinetyFour\PromoBar\Commands\Tick;
use NineteenNinetyFour\PromoBar\Commands\Transfer;
use NineteenNinetyFour\PromoBar\Contracts\BarRepository;
use NineteenNinetyFour\PromoBar\Database\EloquentBarRepository;
use NineteenNinetyFour\PromoBar\Events\BarDeleted;
use NineteenNinetyFour\PromoBar\Events\BarSaved;
use NineteenNinetyFour\PromoBar\Events\BarsReordered;
use NineteenNinetyFour\PromoBar\Fieldtypes\PromoBarPreview;
use NineteenNinetyFour\PromoBar\Http\Middleware\SyncAudienceCookie;
use NineteenNinetyFour\PromoBar\Listeners\UpdateAudienceCookie;
use NineteenNinetyFour\PromoBar\Stache\BarsStore;
use NineteenNinetyFour\PromoBar\Stache\StacheBarRepository;
use NineteenNinetyFour\PromoBar\Tags\PromoBarTags;
use Statamic\Events\AddonSettingsSaved;
use Statamic\Facades\CP\Nav;
use Statamic\Facades\Permission;
use Statamic\Facades\Stache;
use Statamic\Providers\AddonServiceProvider;
use Statamic\Statamic;

class ServiceProvider extends AddonServiceProvider
{
    /** The Control Panel nav icon (resources/brand/nav-icon.svg), a placeholder until the brand kit arrives. */
    public const ICON = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14"><path d="M1.5 5.5v3h2l5 3v-9l-5 3z" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"/><path d="M10.5 5a2.5 2.5 0 0 1 0 4M3.5 8.5l1 3.5h1.5l-.5-3" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"/></svg>';

    protected $tags = [
        PromoBarTags::class,
    ];

    protected $fieldtypes = [
        PromoBarPreview::class,
    ];

    protected $commands = [
        Tick::class,
        Transfer::class,
    ];

    protected $middlewareGroups = [
        'statamic.web' => [SyncAudienceCookie::class],
    ];

    protected $vite = [
        'input' => [
            'resources/js/cp.js',
            'resources/css/cp.css',
        ],
        'publicDirectory' => 'resources/dist',
        'hotFile' => __DIR__.'/../resources/dist/hot',
    ];

    /**
     * The package is 1994/promo-bar-statamic; its config, views and
     * translations are read as plain "promo-bar" instead, so they're loaded
     * by hand. Routes too: actions live at /!/promo-bar.
     */
    protected $config = false;

    protected $translations = false;

    protected $routes = [
        'web' => null,
        'cp' => __DIR__.'/../routes/cp.php',
        'actions' => null,
    ];

    public function register(): void
    {
        parent::register();

        $this->mergeConfigFrom(__DIR__.'/../config/promo-bar.php', 'promo-bar');

        // The file driver's bars are cached by the Stache, and Laravel 13's
        // cache unserialises only the classes it is told about.
        $this->registerSerializableClasses([Bar::class, CarbonImmutable::class]);

        $this->app->singleton(BarRepository::class, fn ($app) => $app->make(static::repositoryFor(config('promo-bar.driver', 'file'))));
        $this->app->singleton(PromoBars::class);
        $this->app->singleton(Settings::class);
        $this->app->singleton(Integrations\Cargo::class);
        $this->app->scoped(Renderer::class);
    }

    /**
     * The repository class for a driver name.
     */
    public static function repositoryFor(string $driver): string
    {
        return match ($driver) {
            'file' => StacheBarRepository::class,
            'database' => EloquentBarRepository::class,
            default => throw new InvalidArgumentException("Unknown promo bar driver [{$driver}]. Use file or database."),
        };
    }

    public function bootAddon(): void
    {
        // Read by the script, so not encrypted. It isn't a secret.
        EncryptCookies::except(app(AudienceCookie::class)->name());

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'promo-bar');

        $this->publishes([__DIR__.'/../config/promo-bar.php' => config_path('promo-bar.php')], 'promo-bar-config');
        $this->publishes([__DIR__.'/../resources/views' => resource_path('views/vendor/promo-bar')], 'promo-bar-views');
        $this->publishes([__DIR__.'/../database/migrations' => database_path('migrations')], 'promo-bar-migrations');

        if (config('promo-bar.driver') === 'database') {
            $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        }

        $this->registerSettingsBlueprint(fn () => app(SettingsBlueprint::class)->contents());

        $this->registerStacheStore();
        $this->registerFrontendRoutes();
        $this->registerCacheClearing();

        Event::listen([Login::class, Logout::class], UpdateAudienceCookie::class);

        Permission::extend(function () {
            Permission::group('promo-bar', 'Promo Bar', function () {
                Permission::register('view promo bars', function ($permission) {
                    $permission->label('View promo bars')->children([
                        Permission::make('edit promo bars')->label('Create, edit, reorder and delete promo bars'),
                    ]);
                });
            });
        });

        Nav::extend(function ($nav) {
            $nav->content('Promo Bars')
                ->route('promo-bar.index')
                ->icon(self::ICON)
                ->can('view promo bars');
        });
    }

    protected function schedule(Schedule $schedule)
    {
        if (config('promo-bar.static_caching.schedule', true)) {
            $schedule->command('promo-bar:tick')->everyMinute()->withoutOverlapping();
        }
    }

    /**
     * The file driver's bars are a Stache store of their own, so they're
     * indexed and cached as entries are, and warmed and cleared with
     * `php please stache:*`.
     */
    protected function registerStacheStore(): void
    {
        $store = app(BarsStore::class)->directory(config('promo-bar.drivers.file.directory', base_path('content/promo-bars')));

        Stache::registerStore($store);
    }

    protected function registerFrontendRoutes(): void
    {
        Statamic::pushActionRoutes(function () {
            Route::prefix('promo-bar')->name('promo-bar.')->group(__DIR__.'/../routes/actions.php');
        });
    }

    /**
     * Cached pages are cleared when bars change, so they show the new bars.
     */
    protected function registerCacheClearing(): void
    {
        Event::listen(BarSaved::class, function (BarSaved $event) {
            app(Invalidator::class)->bars([$event->bar, $event->original]);

            // And any bar that started or ended since the last tick, which
            // this would otherwise mark as done.
            app(Fingerprint::class)->check(flushWhenUnknown: false, handled: [$event->bar->id()]);
        });

        Event::listen(BarDeleted::class, function (BarDeleted $event) {
            app(Invalidator::class)->bars([$event->bar]);
            app(Fingerprint::class)->check(flushWhenUnknown: false, handled: [$event->bar->id()]);
        });

        // Order and settings change what's shown everywhere.
        Event::listen(BarsReordered::class, fn () => app(Invalidator::class)->flush());

        Event::listen(AddonSettingsSaved::class, function (AddonSettingsSaved $event) {
            if ($event->settings->addon()->package() === Settings::PACKAGE) {
                app(Settings::class)->refresh();
                app(Invalidator::class)->flush();
            }
        });
    }
}
