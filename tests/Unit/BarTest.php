<?php

namespace NineteenNinetyFour\PromoBar\Tests\Unit;

use Carbon\CarbonImmutable;
use NineteenNinetyFour\PromoBar\Bar;
use NineteenNinetyFour\PromoBar\BarStatus;
use NineteenNinetyFour\PromoBar\Tests\TestCase;

class BarTest extends TestCase
{
    public function test_a_new_bar_is_on_everywhere_for_everyone_and_dismissible(): void
    {
        $bar = Bar::make(['title' => 'Sale']);

        $this->assertTrue($bar->enabled());
        $this->assertSame('everywhere', $bar->get('pages'));
        $this->assertSame('everyone', $bar->get('audience'));
        $this->assertTrue($bar->get('dismissible'));
        $this->assertMatchesRegularExpression('/^[0-9a-f-]{36}$/', $bar->id());
    }

    public function test_status_follows_the_switch_and_dates(): void
    {
        $now = CarbonImmutable::parse('2026-10-15 12:00', 'Europe/London');

        $this->assertSame(BarStatus::Live, Bar::make()->status($now));
        $this->assertSame(BarStatus::Disabled, Bar::make(['enabled' => false])->status($now));
        $this->assertSame(BarStatus::Scheduled, Bar::make(['starts_at' => '2026-10-16 09:00'])->status($now));
        $this->assertSame(BarStatus::Expired, Bar::make(['ends_at' => '2026-10-15 12:00'])->status($now));
        $this->assertSame(BarStatus::Live, Bar::make(['starts_at' => '2026-10-15 12:00', 'ends_at' => '2026-10-15 12:01'])->status($now));
    }

    public function test_dates_are_read_in_the_apps_timezone(): void
    {
        $bar = Bar::make(['ends_at' => '2026-07-01 18:00']);

        $this->assertSame('2026-07-01T18:00:00+01:00', $bar->endsAt()->toIso8601String());
    }

    public function test_the_content_hash_changes_with_the_message_but_not_the_name(): void
    {
        $bar = Bar::make(['title' => 'A', 'message' => 'One']);
        $hash = $bar->contentHash();

        $this->assertSame($hash, $bar->set('title', 'B')->contentHash());
        $this->assertNotSame($hash, $bar->set('message', 'Two')->contentHash());
    }

    public function test_a_duplicate_is_a_switched_off_copy(): void
    {
        $bar = Bar::make(['title' => 'Sale', 'message' => 'Hi']);
        $copy = $bar->duplicate();

        $this->assertNotSame($bar->id(), $copy->id());
        $this->assertSame('Sale (copy)', $copy->title());
        $this->assertFalse($copy->enabled());
        $this->assertSame('Hi', $copy->get('message'));
    }

    public function test_file_data_reads_back_the_same(): void
    {
        $bar = Bar::make(['title' => 'Sale', 'sites' => ['default']])->order(3)->touch();
        $read = Bar::fromArray($bar->id(), $bar->fileData());

        $this->assertSame($bar->toArray(), $read->toArray());
        $this->assertSame(3, $read->order());
    }

    public function test_an_empty_site_list_means_every_site(): void
    {
        $this->assertTrue(Bar::make()->showsOnSite('fr'));
        $this->assertFalse(Bar::make(['sites' => ['en']])->showsOnSite('fr'));
    }
}
