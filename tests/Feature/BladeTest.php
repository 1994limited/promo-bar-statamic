<?php

namespace NineteenNinetyFour\PromoBar\Tests\Feature;

use NineteenNinetyFour\PromoBar\Renderer;
use NineteenNinetyFour\PromoBar\Tests\TestCase;

/**
 * The Blade versions of the docs' examples.
 */
class BladeTest extends TestCase
{
    protected function renderView(string $view, array $data = []): string
    {
        app(Renderer::class)->reset();

        return view($view, $data)->render();
    }

    public function test_the_tag_and_assets_as_blade_components(): void
    {
        $this->bar(['title' => 'One'], 'First <bar>');
        $this->bar(['title' => 'Two']);

        $html = $this->renderView('blade-page');

        $this->assertLessThan(strpos($html, '<body>'), strpos($html, 'promo-bar.css'));
        $this->assertSame(1, substr_count($html, 'promo-bar.js?v='));
        $this->assertStringContainsString('class="promo-bar promo-bar--stack site-promos"', $html);
        $this->assertStringContainsString('First &lt;bar&gt;', $html);
        $this->assertStringContainsString('data-promo-bar-name="Two"', $html);
    }

    public function test_statamic_tag_fetch(): void
    {
        $this->bar();

        $html = $this->renderView('blade-fetch');

        $this->assertStringContainsString('data-mode="rotate"', $html);
        $this->assertStringContainsString('aria-label="Offers"', $html);
    }
}
