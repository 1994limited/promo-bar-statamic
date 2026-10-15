<?php

namespace NineteenNinetyFour\PromoBar\Tests\Feature;

use NineteenNinetyFour\PromoBar\PromoBars;
use NineteenNinetyFour\PromoBar\Styles;
use NineteenNinetyFour\PromoBar\Tests\TestCase;

class PhonesTest extends TestCase
{
    public function test_a_bar_shows_everything_on_phones_by_default(): void
    {
        $this->bar();

        $html = $this->antlers('{{ promo_bar }}');

        $this->assertDoesNotMatchRegularExpression('/class="promo-bar__bar[^"]*promo-bar--phone-/', $html);
        $this->assertStringContainsString('data-phone-width="640"', $html);
        $this->assertStringContainsString('<div class="promo-bar__message" data-promo-bar-message>', $html);
    }

    public function test_a_phone_message(): void
    {
        $this->bar(['phone_message' => [['type' => 'text', 'marks' => [['type' => 'bold']], 'text' => 'Sale:'], ['type' => 'text', 'text' => ' 20% off']]], 'Autumn sale: 20% off every bag of beans');

        $html = $this->antlers('{{ promo_bar }}');

        $this->assertStringContainsString('<div class="promo-bar__message promo-bar__message--wide" data-promo-bar-message>Autumn sale: 20% off every bag of beans</div>', $html);
        $this->assertStringContainsString('<div class="promo-bar__message promo-bar__message--phone" data-promo-bar-message><strong>Sale:</strong> 20% off</div>', $html);
    }

    public function test_the_phone_message_is_sanitised_too(): void
    {
        $this->bar(['phone_message' => [['type' => 'text', 'marks' => [['type' => 'link', 'attrs' => ['href' => 'javascript:alert(1)']]], 'text' => 'x']]]);

        $this->assertStringNotContainsString('javascript:', $this->antlers('{{ promo_bar }}'));
    }

    public function test_parts_left_out_on_phones(): void
    {
        $this->bar(['phone_countdown' => false, 'phone_code' => false, 'phone_button' => true]);

        $html = $this->antlers('{{ promo_bar }}');

        $this->assertStringContainsString('promo-bar--phone-no-countdown promo-bar--phone-no-code"', $html);
        $this->assertStringNotContainsString('promo-bar--phone-no-button', $html);
    }

    public function test_a_bar_not_for_phones(): void
    {
        $this->bar(['phone_show' => false, 'phone_message' => [['type' => 'text', 'text' => 'Short']]]);

        $html = $this->antlers('{{ promo_bar }}');

        $this->assertMatchesRegularExpression('/class="promo-bar__bar[^"]*promo-bar--phone-hidden/', $html);
        $this->assertStringNotContainsString('promo-bar__message--phone', $html);
    }

    public function test_stacked_bars_show_only_the_first_on_phones_unless_turned_off(): void
    {
        $this->bar();

        $this->assertStringContainsString('promo-bar--stack promo-bar--phone-first', $this->antlers('{{ promo_bar mode="stack" }}'));
        $this->assertStringNotContainsString('promo-bar--phone-first', $this->antlers('{{ promo_bar mode="rotate" }}'));

        $this->settings(['phone_first_only' => false]);

        $this->assertStringNotContainsString('promo-bar--phone-first', $this->antlers('{{ promo_bar mode="stack" }}'));
    }

    public function test_the_phone_width_is_in_the_stylesheet_and_page(): void
    {
        $this->settings(['phone_width' => 480]);
        $this->bar();

        $css = app(Styles::class)->css(app(PromoBars::class)->liveAnywhere());

        $this->assertStringContainsString("@media (max-width: 480px) {\n", $css);
        $this->assertStringContainsString('.promo-bar--phone-hidden { display: none !important; }', $css);
        $this->assertStringContainsString('data-phone-width="480"', $this->antlers('{{ promo_bar }}'));
    }

    public function test_the_phone_width_is_kept_within_sense(): void
    {
        $this->settings(['phone_width' => '99999']);
        $this->bar();

        $this->assertStringContainsString('data-phone-width="1200"', $this->antlers('{{ promo_bar }}'));
    }

    public function test_changing_the_phone_message_shows_a_dismissed_bar_again(): void
    {
        $bar = $this->bar();
        $hash = $bar->contentHash();

        $this->assertNotSame($hash, $bar->set('phone_message', [['type' => 'text', 'text' => 'New']])->contentHash());
    }
}
