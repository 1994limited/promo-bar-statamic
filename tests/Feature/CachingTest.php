<?php

namespace NineteenNinetyFour\PromoBar\Tests\Feature;

use Carbon\CarbonImmutable;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Cache;
use Mockery;
use NineteenNinetyFour\PromoBar\Caching\Fingerprint;
use NineteenNinetyFour\PromoBar\Tests\TestCase;
use Statamic\Facades\Addon;
use Statamic\Facades\StaticCache;
use Statamic\StaticCaching\Cacher;

class CachingTest extends TestCase
{
    protected $cacher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->staticCaching();
        $this->cacher = Mockery::spy(Cacher::class);
        $this->app->instance(Cacher::class, $this->cacher);
        StaticCache::spy();
    }

    public function test_nothing_is_cleared_without_static_caching(): void
    {
        config(['statamic.static_caching.strategy' => null]);

        $this->bar();

        StaticCache::shouldNotHaveReceived('flush');
    }

    public function test_nothing_is_cleared_when_switched_off(): void
    {
        config(['promo-bar.static_caching.invalidate' => false]);

        $this->bar();

        StaticCache::shouldNotHaveReceived('flush');
    }

    public function test_a_bar_shown_everywhere_clears_everything(): void
    {
        $this->bar();

        StaticCache::shouldHaveReceived('flush')->once();
    }

    public function test_a_bar_on_a_few_pages_clears_only_those(): void
    {
        $this->page('about', 'about');
        $this->bar(['pages' => 'only', 'entries' => ['about'], 'urls' => "/sale\n/shop/*"]);

        StaticCache::shouldNotHaveReceived('flush');
        $this->cacher->shouldHaveReceived('invalidateUrls')->with(['http://localhost/about', 'http://localhost/sale', 'http://localhost/shop/*'])->once();
    }

    public function test_moving_a_bar_clears_where_it_was_and_where_it_is(): void
    {
        $bar = $this->bar(['pages' => 'only', 'urls' => '/old']);
        $this->cacher->shouldHaveReceived('invalidateUrls')->with(['http://localhost/old'])->once();

        $this->bars()->save($bar->set('urls', '/new'));

        $this->cacher->shouldHaveReceived('invalidateUrls')->with(['http://localhost/new', 'http://localhost/old'])->once();
    }

    public function test_whole_collections_and_except_clear_everything(): void
    {
        $this->bar(['pages' => 'only', 'collections' => ['products']]);
        $this->bar(['pages' => 'except', 'urls' => '/checkout']);

        StaticCache::shouldHaveReceived('flush')->twice();
    }

    public function test_deleting_and_reordering(): void
    {
        $a = $this->bar(['pages' => 'only', 'urls' => '/a']);
        $b = $this->bar(['pages' => 'only', 'urls' => '/b']);

        $this->bars()->delete($a);
        $this->cacher->shouldHaveReceived('invalidateUrls')->with(['http://localhost/a'])->twice();

        $this->bars()->reorder([$b->id()]);
        StaticCache::shouldHaveReceived('flush')->once();
    }

    public function test_saving_the_settings_clears_everything(): void
    {
        $addon = Addon::get('1994/promo-bar-statamic');
        $addon->settings()->set(['display' => 'stack'])->save();

        StaticCache::shouldHaveReceived('flush')->once();
    }

    public function test_the_tick_clears_pages_when_a_bar_starts_or_ends(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-15 23:58', 'Europe/London'));
        $this->bar(['pages' => 'only', 'urls' => '/sale', 'starts_at' => '2026-10-16 00:00', 'ends_at' => '2026-10-17 00:00']);
        $this->cacher->shouldHaveReceived('invalidateUrls')->once();

        $this->artisan('promo-bar:tick')->assertSuccessful();
        $this->cacher->shouldHaveReceived('invalidateUrls')->once();

        $this->travelTo(CarbonImmutable::parse('2026-10-16 00:00', 'Europe/London'));
        $this->artisan('promo-bar:tick')->expectsOutputToContain('A bar started or ended')->assertSuccessful();
        $this->cacher->shouldHaveReceived('invalidateUrls')->twice();

        $this->travelTo(CarbonImmutable::parse('2026-10-16 12:00', 'Europe/London'));
        $this->artisan('promo-bar:tick')->assertSuccessful();
        $this->cacher->shouldHaveReceived('invalidateUrls')->twice();

        $this->travelTo(CarbonImmutable::parse('2026-10-17 00:00', 'Europe/London'));
        $this->artisan('promo-bar:tick')->assertSuccessful();
        $this->cacher->shouldHaveReceived('invalidateUrls')->times(3);
    }

    public function test_the_first_tick_clears_everything_once(): void
    {
        $this->bar(['pages' => 'only', 'urls' => '/sale']);
        Cache::forget(Fingerprint::KEY);

        $this->artisan('promo-bar:tick')->assertSuccessful();
        StaticCache::shouldHaveReceived('flush')->once();

        $this->artisan('promo-bar:tick')->assertSuccessful();
        StaticCache::shouldHaveReceived('flush')->once();
    }

    public function test_a_save_with_no_record_leaves_the_first_flush_to_the_tick(): void
    {
        Cache::forget(Fingerprint::KEY);
        $this->bar(['pages' => 'only', 'urls' => '/other']);
        StaticCache::shouldNotHaveReceived('flush');

        $this->assertNull(Cache::get(Fingerprint::KEY));

        $this->artisan('promo-bar:tick')->assertSuccessful();
        StaticCache::shouldHaveReceived('flush')->once();
    }

    public function test_saving_another_bar_doesnt_swallow_a_start_the_tick_hasnt_seen(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-15 09:59', 'Europe/London'));
        $this->bar(['title' => 'Starts', 'pages' => 'only', 'urls' => '/starts', 'starts_at' => '2026-10-15 10:00']);
        $other = $this->bar(['title' => 'Other', 'pages' => 'only', 'urls' => '/other']);
        $this->artisan('promo-bar:tick')->assertSuccessful();

        $this->travelTo(CarbonImmutable::parse('2026-10-15 10:00:20', 'Europe/London'));
        $this->bars()->save($other->set('title', 'Other, renamed'));

        // Saved before the tick ran: the bar that started has its pages cleared too.
        $this->cacher->shouldHaveReceived('invalidateUrls')->with(['http://localhost/starts'])->twice();
    }

    public function test_the_tick_is_scheduled(): void
    {
        $events = collect(app(Schedule::class)->events())->map->command->filter();

        $this->assertTrue($events->contains(fn ($command) => str_contains($command, 'promo-bar:tick')));
    }
}
