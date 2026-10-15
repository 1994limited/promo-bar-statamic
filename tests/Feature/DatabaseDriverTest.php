<?php

namespace NineteenNinetyFour\PromoBar\Tests\Feature;

use NineteenNinetyFour\PromoBar\Contracts\BarRepository;
use NineteenNinetyFour\PromoBar\Database\EloquentBarRepository;
use NineteenNinetyFour\PromoBar\Tests\TestCase;

class DatabaseDriverTest extends TestCase
{
    use RepositoryContract;

    protected string $driver = 'database';

    public function test_it_uses_the_database(): void
    {
        $this->assertInstanceOf(EloquentBarRepository::class, app(BarRepository::class));

        $bar = $this->bar(['title' => 'In the database']);

        $this->assertDatabaseHas('promo_bars', ['id' => $bar->id()]);
    }

    public function test_the_tag_works_the_same(): void
    {
        $this->bar([], 'From the database');

        $this->assertStringContainsString('From the database', $this->antlers('{{ promo_bar }}'));
    }
}
