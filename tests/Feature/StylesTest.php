<?php

namespace NineteenNinetyFour\PromoBar\Tests\Feature;

use NineteenNinetyFour\PromoBar\PromoBars;
use NineteenNinetyFour\PromoBar\Styles;
use NineteenNinetyFour\PromoBar\Tests\TestCase;

class StylesTest extends TestCase
{
    protected function css(): string
    {
        return app(Styles::class)->css(app(PromoBars::class)->liveAnywhere());
    }

    public function test_every_preset_has_its_custom_properties(): void
    {
        $css = $this->css();

        foreach (array_keys(Styles::presets()) as $preset) {
            $this->assertStringContainsString(":where(.promo-bar--{$preset}){--promo-bar-background:", $css);
        }

        $this->assertStringContainsString(':where(.promo-bar--gradient){--promo-bar-background:#6d28d9;--promo-bar-background-2:#be185d', $css);
        $this->assertStringContainsString('.promo-bar { display: grid; }', $css);
    }

    public function test_preset_values_have_no_specificity_so_the_sites_own_css_wins(): void
    {
        $this->assertStringContainsString(':where(.promo-bar--solid){--promo-bar-background:', $this->css());
    }

    public function test_presets_are_changed_in_the_settings(): void
    {
        $this->settings(['style_solid' => ['background' => '#FF0000', 'text' => '#000000', 'size' => 'large', 'padding' => 'compact', 'align' => 'start', 'radius' => 0, 'button_style' => 'outline']]);

        $css = $this->css();

        $this->assertStringContainsString(':where(.promo-bar--solid){--promo-bar-background:#ff0000;--promo-bar-text:#000000;', $css);
        $this->assertStringContainsString('--promo-bar-font-size:1.0625rem;--promo-bar-padding:0.375rem;--promo-bar-align:flex-start;--promo-bar-text-align:start;--promo-bar-radius:0px', $css);
    }

    public function test_values_that_arent_colours_cant_break_out_of_the_css(): void
    {
        $this->settings(['style_solid' => ['background' => 'red;}body{display:none', 'size' => '"><script>', 'radius' => 'x']]);

        $css = $this->css();

        $this->assertStringNotContainsString('display:none', $css);
        $this->assertStringNotContainsString('<script>', $css);
        $this->assertStringContainsString(':where(.promo-bar--solid){--promo-bar-background:#1d4ed8', $css);
    }

    public function test_a_bars_own_colours(): void
    {
        $bar = $this->bar(['custom_colours' => true, 'colour_background' => '#000000', 'colour_text' => 'nope']);

        $css = $this->css();

        $this->assertStringContainsString('.promo-bar__bar[data-promo-bar-id="'.$bar->id().'"]{--promo-bar-background:#000000}', $css);
    }

    public function test_bars_not_yet_live_arent_in_the_stylesheet(): void
    {
        $bar = $this->bar(['custom_colours' => true, 'colour_background' => '#000000', 'starts_at' => now()->addWeek()->format('Y-m-d H:i')]);

        $this->assertStringNotContainsString($bar->id(), $this->css());
    }

    public function test_a_bars_style_or_the_default(): void
    {
        $this->settings(['default_style' => 'dark']);

        $this->assertStringContainsString('promo-bar--dark promo-bar--themed', $this->antlersFor($this->bar()));
        $this->assertStringContainsString('promo-bar--outline promo-bar--themed promo-bar--button-outline', $this->antlersFor($this->bar(['style' => 'outline'])));
    }

    public function test_none_is_unstyled(): void
    {
        $html = $this->antlersFor($this->bar(['style' => 'none']));

        $this->assertStringContainsString('class="promo-bar__bar promo-bar--none promo-bar--dismissible"', $html);
    }

    public function test_the_stylesheet_url_changes_with_the_settings(): void
    {
        $this->bar();
        preg_match('/promo-bar\.css\?v=(\w+)/', $this->antlers('{{ promo_bar }}'), $before);

        $this->settings(['style_solid' => ['background' => '#123456']]);
        preg_match('/promo-bar\.css\?v=(\w+)/', $this->antlers('{{ promo_bar }}'), $after);

        $this->assertNotSame($before[1], $after[1]);
    }

    public function test_contrast(): void
    {
        $this->assertSame(21.0, Styles::contrast('#000', '#fff'));
        $this->assertSame(4.48, Styles::contrast('#777777', '#ffffff'));
    }

    protected function antlersFor($bar): string
    {
        return collect(explode('<section', $this->antlers('{{ promo_bar mode="stack" }}')))
            ->first(fn ($part) => str_contains($part, $bar->id())) ?? '';
    }
}
