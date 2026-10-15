<?php

namespace NineteenNinetyFour\PromoBar\Tests\Feature;

use NineteenNinetyFour\PromoBar\Database\EloquentBarRepository;
use NineteenNinetyFour\PromoBar\Stache\StacheBarRepository;
use NineteenNinetyFour\PromoBar\Tests\TestCase;

class TransferTest extends TestCase
{
    public function test_it_needs_the_table(): void
    {
        $this->artisan('promo-bar:transfer', ['--from' => 'file', '--to' => 'database', '--force' => true])
            ->expectsOutputToContain('vendor:publish --tag=promo-bar-migrations')
            ->assertFailed();
    }

    public function test_it_checks_the_drivers(): void
    {
        $this->artisan('promo-bar:transfer', ['--from' => 'file', '--to' => 'file'])->assertFailed();
        $this->artisan('promo-bar:transfer', ['--from' => 'file', '--to' => 'redis'])->assertFailed();
    }

    public function test_files_to_the_database_and_back(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');

        $a = $this->bar(['title' => 'A']);
        $b = $this->bar(['title' => 'B']);

        $this->artisan('promo-bar:transfer', ['--from' => 'file', '--to' => 'database', '--force' => true])
            ->expectsOutputToContain('Copied 2 bars to the database driver')
            ->assertSuccessful();

        $database = app(EloquentBarRepository::class);
        $this->assertSame(['A', 'B'], $database->all()->map->title()->all());
        $this->assertSame($a->get('message'), $database->find($a->id())->get('message'));

        app(StacheBarRepository::class)->delete($b);
        $this->artisan('promo-bar:transfer', ['--from' => 'database', '--to' => 'file', '--force' => true])->assertSuccessful();

        $this->assertNotNull(app(StacheBarRepository::class)->find($b->id()));
    }
}
