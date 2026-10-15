<?php

namespace NineteenNinetyFour\PromoBar\Tests\Feature;

use NineteenNinetyFour\PromoBar\Bar;
use NineteenNinetyFour\PromoBar\BarStatus;
use NineteenNinetyFour\PromoBar\Page;
use NineteenNinetyFour\PromoBar\PromoBars;
use NineteenNinetyFour\PromoBar\Renderer;
use NineteenNinetyFour\PromoBar\Tests\TestCase;

/**
 * The examples in docs/php-api.md, as written.
 */
class DocsTest extends TestCase
{
    public function test_the_php_api_example(): void
    {
        $this->travelTo('2026-04-01 12:00');

        $bars = app(PromoBars::class);

        $bar = Bar::make([
            'title' => 'Spring launch',
            'message' => [['type' => 'text', 'text' => 'New spring blend out now']],
            'cta_label' => 'Have a look',
            'cta_url' => '/shop',
            'ends_at' => '2026-04-30 23:59',
        ]);

        $bars->save($bar);
        $other = $this->bar(['title' => 'Other']);

        $this->assertSame($bar->id(), $bars->find($bar->id())->id());
        $this->assertTrue($bars->isLive($bar));
        $this->assertSame(BarStatus::Live, $bar->status());
        $this->assertCount(2, $bars->live(Page::current()));
        $this->assertCount(2, $bars->liveAnywhere());

        $bars->reorder([$other->id()]);
        $this->assertSame(['Other', 'Spring launch'], $bars->all()->map->title()->all());

        $html = app(Renderer::class)->render(Page::current(), ['mode' => 'stack']);
        $this->assertStringContainsString('New spring blend out now', $html);
        $this->assertStringContainsString('data-mode="stack"', $html);

        $bars->delete($bar);
        $this->assertNull($bars->find($bar->id()));
    }

    public function test_the_published_view_is_used(): void
    {
        $this->bar();
        $this->app['view']->getFinder()->prependNamespace('promo-bar', __DIR__.'/../__fixtures__/published/promo-bar');

        $this->assertStringContainsString('<div class="my-bars">Free delivery this weekend</div>', $this->antlers('{{ promo_bar }}'));
    }
}
