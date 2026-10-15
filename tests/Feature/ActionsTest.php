<?php

namespace NineteenNinetyFour\PromoBar\Tests\Feature;

use NineteenNinetyFour\PromoBar\Tests\TestCase;

class ActionsTest extends TestCase
{
    public function test_the_stylesheet(): void
    {
        $response = $this->get('/!/promo-bar/promo-bar.css?v=abc')->assertOk();

        $this->assertStringStartsWith('text/css', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('immutable', $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('.promo-bar--solid{', $response->getContent());
    }

    public function test_the_script(): void
    {
        $response = $this->get('/!/promo-bar/promo-bar.js?v=abc')->assertOk();

        $this->assertStringStartsWith('application/javascript', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('window.PromoBar', $response->getContent());
    }

    public function test_without_a_version_they_arent_kept_for_long(): void
    {
        $this->assertStringContainsString('max-age=300', $this->get('/!/promo-bar/promo-bar.js')->headers->get('Cache-Control'));
    }

    public function test_the_urls_in_the_page_serve_these_files(): void
    {
        $this->bar();
        $html = $this->antlers('{{ promo_bar }}');

        preg_match('#href="http://localhost(/!/promo-bar/promo-bar\.css\?v=\w+)"#', $html, $css);
        preg_match('#src="http://localhost(/!/promo-bar/promo-bar\.js\?v=\w+)"#', $html, $js);

        $this->get($css[1])->assertOk();
        $this->get($js[1])->assertOk();
    }

    public function test_state_without_cargo_or_with_nonsense(): void
    {
        $bar = $this->bar();

        $this->getJson('/!/promo-bar/state?bars='.$bar->id().',../../etc,unknown')
            ->assertOk()
            ->assertExactJson(['bars' => [$bar->id() => null, 'unknown' => null]]);

        $this->getJson('/!/promo-bar/state')->assertOk()->assertExactJson(['bars' => []]);
    }
}
