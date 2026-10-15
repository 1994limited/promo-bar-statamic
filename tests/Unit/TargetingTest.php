<?php

namespace NineteenNinetyFour\PromoBar\Tests\Unit;

use Illuminate\Http\Request;
use NineteenNinetyFour\PromoBar\Bar;
use NineteenNinetyFour\PromoBar\Page;
use NineteenNinetyFour\PromoBar\Targeting;
use NineteenNinetyFour\PromoBar\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class TargetingTest extends TestCase
{
    protected function shows(array $bar, Page $page): bool
    {
        return (new Targeting)->matches(Bar::make($bar), $page);
    }

    public function test_everywhere_is_every_page(): void
    {
        $this->assertTrue($this->shows([], new Page('default', '/anything')));
    }

    public function test_only_on_entries(): void
    {
        $bar = ['pages' => 'only', 'entries' => ['home']];

        $this->assertTrue($this->shows($bar, new Page('default', '/', 'home', 'pages')));
        $this->assertFalse($this->shows($bar, new Page('default', '/about', 'about', 'pages')));
    }

    public function test_only_in_collections(): void
    {
        $bar = ['pages' => 'only', 'collections' => ['products']];

        $this->assertTrue($this->shows($bar, new Page('default', '/shop/mug', 'mug', 'products')));
        $this->assertFalse($this->shows($bar, new Page('default', '/about', 'about', 'pages')));
    }

    public function test_everywhere_except(): void
    {
        $bar = ['pages' => 'except', 'urls' => "/checkout*\n/basket"];

        $this->assertTrue($this->shows($bar, new Page('default', '/shop')));
        $this->assertFalse($this->shows($bar, new Page('default', '/checkout/pay')));
        $this->assertFalse($this->shows($bar, new Page('default', '/basket')));
    }

    public function test_sites(): void
    {
        $this->assertFalse($this->shows(['sites' => ['fr']], new Page('default', '/')));
        $this->assertTrue($this->shows(['sites' => ['default', 'fr']], new Page('default', '/')));
    }

    public static function patterns(): array
    {
        return [
            'exact' => ['/sale', '/sale', true],
            'trailing slash' => ['/sale/', '/sale', true],
            'no leading slash' => ['sale', '/sale', true],
            'case' => ['/Sale', '/sale', true],
            'not a prefix' => ['/sale', '/sale/shoes', false],
            'wildcard' => ['/shop/*', '/shop/mugs/blue', true],
            'wildcard needs the slash' => ['/shop/*', '/shopping', false],
            'leading wildcard' => ['*/sale', '/en/sale', true],
            'full URL' => ['https://example.com/sale', '/sale', true],
            'home' => ['/', '/', true],
            'regex characters are literal' => ['/a.b', '/axb', false],
        ];
    }

    #[DataProvider('patterns')]
    public function test_url_patterns(string $pattern, string $path, bool $expected): void
    {
        $this->assertSame($expected, $this->shows(['pages' => 'only', 'urls' => $pattern], new Page('default', Page::normalise($path))));
    }

    public function test_the_page_is_found_from_the_url_when_the_template_doesnt_say(): void
    {
        $this->page('about', 'about');

        $page = Page::current(Request::create('/about/'));

        $this->assertSame('about', $page->entry);
        $this->assertSame('pages', $page->collection);
        $this->assertSame('/about', $page->path);
    }
}
