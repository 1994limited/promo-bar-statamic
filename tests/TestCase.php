<?php

namespace NineteenNinetyFour\PromoBar\Tests;

use NineteenNinetyFour\PromoBar\Bar;
use NineteenNinetyFour\PromoBar\PromoBars;
use NineteenNinetyFour\PromoBar\Renderer;
use NineteenNinetyFour\PromoBar\ServiceProvider;
use NineteenNinetyFour\PromoBar\Settings;
use Statamic\Contracts\Addons\SettingsRepository;
use Statamic\Facades\Addon;
use Statamic\Facades\Antlers;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Statamic\Facades\User;
use Statamic\Facades\UserGroup;
use Statamic\Testing\AddonTestCase;
use Statamic\Testing\Concerns\FakesRoles;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

abstract class TestCase extends AddonTestCase
{
    use FakesRoles, PreventsSavingStacheItemsToDisk;

    protected string $addonServiceProvider = ServiceProvider::class;

    /** The storage driver the test runs with. */
    protected string $driver = 'file';

    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('promo-bar.driver', $this->driver);
        $app['config']->set('promo-bar.drivers.file.directory', __DIR__.'/__fixtures__/dev-null/promo-bars');
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite.database', ':memory:');
        $app['config']->set('statamic.editions.pro', true);
        $app['config']->set('app.timezone', 'Europe/London');
        $app['config']->set('view.paths', [__DIR__.'/__fixtures__/views']);
    }

    protected function setUp(): void
    {
        parent::setUp();

        if ($this->driver === 'database') {
            $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        }

        // Addon settings are saved in the test app's resources, and would
        // otherwise carry over to the next test.
        $this->clearSettings();
        $this->beforeApplicationDestroyed(fn () => $this->clearSettings());

        // User groups are saved to the test app's resources too.
        $this->beforeApplicationDestroyed(fn () => @unlink(resource_path('users/groups.yaml')));

        Collection::make('pages')->routes('/{slug}')->save();
        Collection::make('products')->routes('/shop/{slug}')->save();
    }

    protected function bars(): PromoBars
    {
        return app(PromoBars::class);
    }

    /**
     * Save a bar with a plain message (as Bard's inline value).
     */
    protected function bar(array $data = [], string $message = 'Free delivery this weekend'): Bar
    {
        $bar = Bar::make([
            'title' => 'Weekend',
            'message' => [['type' => 'text', 'text' => $message]],
            ...$data,
        ]);

        return $this->bars()->save($bar);
    }

    protected function page(string $id, string $slug): \Statamic\Contracts\Entries\Entry
    {
        $entry = Entry::make()->collection('pages')->id($id)->slug($slug)->data(['title' => ucfirst($slug)]);
        $entry->save();

        return $entry;
    }

    protected function product(string $id, string $slug): \Statamic\Contracts\Entries\Entry
    {
        $entry = Entry::make()->collection('products')->id($id)->slug($slug)->data(['title' => ucfirst($slug)]);
        $entry->save();

        return $entry;
    }

    protected function user(string $email = 'ada@example.com', array $permissions = [], array $groups = []): \Statamic\Contracts\Auth\User
    {
        if ($permissions) {
            $this->setTestRoles(['tester' => ['access cp', ...$permissions]]);
        }

        $user = User::make()->email($email)->id((string) str($email)->before('@')->slug());

        if ($permissions) {
            $user->assignRole('tester');
        }

        foreach ($groups as $group) {
            if (! UserGroup::find($group)) {
                UserGroup::make()->handle($group)->title(ucfirst($group))->save();
            }
            $user->addToGroup($group);
        }

        $user->save();

        return $user;
    }

    protected function clearSettings(): void
    {
        if ($addon = Addon::get(Settings::PACKAGE)) {
            app(SettingsRepository::class)->find($addon->id())?->delete();
        }

        app(Settings::class)->refresh();
    }

    protected function settings(array $values): void
    {
        $addon = Addon::get(Settings::PACKAGE);
        app(SettingsRepository::class)->make($addon, $values)->save();
        app(Settings::class)->refresh();
    }

    /**
     * Render Antlers as a site template would (trusted, so tags run).
     */
    protected function antlers(string $template, array $data = []): string
    {
        app(Renderer::class)->reset();

        return (string) Antlers::parse($template, $data, true);
    }

    protected function staticCaching(string $strategy = 'half'): void
    {
        config(['statamic.static_caching.strategy' => $strategy]);
    }
}
