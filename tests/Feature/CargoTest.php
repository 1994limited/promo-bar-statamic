<?php

namespace NineteenNinetyFour\PromoBar\Tests\Feature;

use Carbon\CarbonImmutable;
use DuncanMcClean\Cargo\Facades\Cart;
use NineteenNinetyFour\PromoBar\Blueprints\BarBlueprint;
use NineteenNinetyFour\PromoBar\Integrations\Cargo;
use NineteenNinetyFour\PromoBar\Tests\CargoTestCase;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

#[RunTestsInSeparateProcesses]
class CargoTest extends CargoTestCase
{
    public function test_cargo_is_found_and_only_supported_versions_used(): void
    {
        $cargo = app(Cargo::class);

        $this->assertTrue($cargo->installed());
        $this->assertTrue($cargo->supported('1.15.0'));
        $this->assertFalse($cargo->supported('1.14.9'));
        $this->assertFalse($cargo->supported('2.0.0'));
    }

    public function test_the_bar_form_has_cargos_fields(): void
    {
        $this->discount(['title' => 'Ten off', 'discount_code' => 'TENOFF', 'percentage_off' => 10]);

        $blueprint = app(BarBlueprint::class)->make();

        $this->assertNotNull($blueprint->field('free_delivery'));
        $this->assertSame('money', $blueprint->field('free_delivery_threshold')->type());
        $this->assertContains('TENOFF – Ten off', $blueprint->field('cargo_discount')->get('options'));
    }

    public function test_a_code_from_a_cargo_discount(): void
    {
        $discount = $this->discount(['title' => 'Ten off', 'discount_code' => 'TENOFF', 'percentage_off' => 10]);
        $this->bar(['code_source' => 'cargo', 'cargo_discount' => $discount->id()]);

        $this->assertStringContainsString('<code data-promo-bar-code>TENOFF</code>', $this->antlers('{{ promo_bar }}'));
    }

    public function test_the_bar_ends_when_the_discount_does(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-15 12:00', 'Europe/London'));
        $discount = $this->discount(['title' => 'Ten off', 'discount_code' => 'TENOFF', 'percentage_off' => 10, 'end_date' => '2026-10-15']);
        $this->bar(['code_source' => 'cargo', 'cargo_discount' => $discount->id()], 'Ten percent off');

        $this->assertStringContainsString('Ten percent off', $this->antlers('{{ promo_bar }}'));

        $this->travelTo(CarbonImmutable::parse('2026-10-16 00:00', 'Europe/London'));

        $this->assertSame('', trim($this->antlers('{{ promo_bar }}')));
    }

    public function test_a_used_up_or_deleted_discount_hides_the_bar(): void
    {
        $discount = $this->discount(['discount_code' => 'ONCE', 'percentage_off' => 10, 'maximum_uses' => 1, 'redemptions_count' => 1]);
        $this->bar(['code_source' => 'cargo', 'cargo_discount' => $discount->id()]);
        $this->bar(['code_source' => 'cargo', 'cargo_discount' => 'gone']);

        $this->assertSame('', trim($this->antlers('{{ promo_bar }}')));
    }

    public function test_free_delivery_shows_the_threshold_until_the_script_fetches_the_basket(): void
    {
        $this->staticCaching();
        $this->bar(['free_delivery' => true, 'free_delivery_threshold' => 5000], 'Free delivery over :threshold');

        $html = $this->antlers('{{ promo_bar }}');

        $this->assertStringContainsString('Free delivery over £50.00', $html);
        $this->assertStringContainsString('data-progress-text=":remaining away from free delivery"', $html);
        $this->assertMatchesRegularExpression('/<p class="promo-bar__progress" data-promo-bar-free-delivery [^>]+ data-fetch hidden>/', $html);
    }

    public function test_without_static_caching_the_progress_is_drawn_on_the_server(): void
    {
        $this->basket(2);
        $this->bar(['free_delivery' => true, 'free_delivery_threshold' => 5000], 'Free delivery over :threshold');

        $html = $this->antlers('{{ promo_bar }}');

        $this->assertStringContainsString('£34.00 away from free delivery', $html);
        $this->assertStringContainsString('value="32"', $html);
        $this->assertStringContainsString('data-promo-bar-message hidden>', $html);
        $this->assertStringNotContainsString('data-fetch', $html);
    }

    public function test_the_state_endpoint(): void
    {
        $bar = $this->bar(['free_delivery' => true, 'free_delivery_threshold' => 5000, 'free_delivery_unlocked' => 'Free delivery is yours']);
        $plain = $this->bar();

        $this->getJson('/!/promo-bar/state?bars='.$bar->id().','.$plain->id())
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertJsonPath('bars.'.$bar->id(), null)
            ->assertJsonPath('bars.'.$plain->id(), null);

        $this->basket(2);

        $this->getJson('/!/promo-bar/state?bars='.$bar->id())
            ->assertOk()
            ->assertJsonPath('bars.'.$bar->id().'.remaining', 3400)
            ->assertJsonPath('bars.'.$bar->id().'.remaining_formatted', '£34.00')
            ->assertJsonPath('bars.'.$bar->id().'.percent', 32)
            ->assertJsonPath('bars.'.$bar->id().'.unlocked', false);

        $this->basket(7);

        $this->getJson('/!/promo-bar/state?bars='.$bar->id())
            ->assertJsonPath('bars.'.$bar->id().'.unlocked', true)
            ->assertJsonPath('bars.'.$bar->id().'.text', 'Free delivery is yours');
    }

    public function test_the_state_endpoint_doesnt_make_a_basket(): void
    {
        $bar = $this->bar(['free_delivery' => true, 'free_delivery_threshold' => 5000]);

        $this->getJson('/!/promo-bar/state?bars='.$bar->id())->assertOk();

        $this->assertSame(0, Cart::query()->count());
    }

    public function test_the_threshold_from_a_free_shipping_discount(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-15 12:00', 'Europe/London'));
        $discount = $this->discount(['title' => 'Free shipping over £40', 'minimum_order_value' => 4000, 'end_date' => '2026-10-31'], 'free_shipping');
        $this->discount(['title' => 'Coded', 'discount_code' => 'SHIP', 'minimum_order_value' => 1000], 'free_shipping');

        $this->assertSame([$discount->id() => 'Free shipping over £40'], app(Cargo::class)->freeShippingOptions());

        $this->bar(['free_delivery' => true, 'free_delivery_source' => 'discount', 'free_delivery_discount' => $discount->id()], 'Free delivery over :threshold');

        $this->assertStringContainsString('Free delivery over £40.00', $this->antlers('{{ promo_bar }}'));

        $this->travelTo(CarbonImmutable::parse('2026-11-01 00:00', 'Europe/London'));

        $this->assertSame('', trim($this->antlers('{{ promo_bar }}')));
    }
}
