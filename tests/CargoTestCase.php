<?php

namespace NineteenNinetyFour\PromoBar\Tests;

use DuncanMcClean\Cargo\Contracts\Cart\Cart as CartContract;
use DuncanMcClean\Cargo\Discounts\DiscountServiceProvider;
use DuncanMcClean\Cargo\Facades\Cart;
use DuncanMcClean\Cargo\Facades\Discount;
use DuncanMcClean\Cargo\Orders\OrderServiceProvider;
use DuncanMcClean\Cargo\Payments\PaymentServiceProvider;
use DuncanMcClean\Cargo\ServiceProvider as CargoServiceProvider;
use DuncanMcClean\Cargo\Shipping\ShippingServiceProvider;
use Illuminate\Filesystem\Filesystem;
use Statamic\Addons\Manifest;
use Statamic\Facades\Blueprint;
use Statamic\Facades\Entry;
use Statamic\Facades\Site;

/**
 * Promo Bar on a site with Cargo installed. Tests using it run each test
 * in its own process (#[RunTestsInSeparateProcesses]): Cargo registers
 * routes, search and more in Statamic's static state, which would
 * otherwise reach tests without it.
 */
abstract class CargoTestCase extends TestCase
{
    /** Where Cargo keeps carts and discounts, and blueprints are read from. */
    protected static string $content = __DIR__.'/__fixtures__/cargo-tmp';

    protected const MUG = '44444444-4444-4444-8444-444444444444';

    protected function getPackageProviders($app)
    {
        return [
            CargoServiceProvider::class,
            DiscountServiceProvider::class,
            OrderServiceProvider::class,
            PaymentServiceProvider::class,
            ShippingServiceProvider::class,
            ...parent::getPackageProviders($app),
        ];
    }

    protected function resolveApplicationConfiguration($app)
    {
        parent::resolveApplicationConfiguration($app);

        $app['config']->set('statamic.cargo', require __DIR__.'/../vendor/duncanmcclean/statamic-cargo/config/cargo.php');
    }

    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        // Statamic boots only the addons in its manifest, and the test case
        // lists just this one. Cargo is installed alongside it on a site.
        $app->make(Manifest::class)->manifest['duncanmcclean/statamic-cargo'] = [
            'id' => 'duncanmcclean/statamic-cargo',
            'slug' => 'cargo',
            'version' => '1.15.0',
            'namespace' => 'DuncanMcClean\\Cargo',
            'autoload' => 'src',
            'provider' => CargoServiceProvider::class,
        ];

        foreach (['carts', 'orders', 'discounts'] as $store) {
            $app['config']->set("statamic.cargo.{$store}.directory", static::$content.'/'.$store);
        }
        $app['config']->set('statamic.cargo.taxes.tax_classes.directory', static::$content.'/tax-classes');
        $app['config']->set('statamic.cargo.taxes.tax_zones.directory', static::$content.'/tax-zones');
    }

    protected function setUp(): void
    {
        $files = new Filesystem;
        $files->deleteDirectory(static::$content);
        $files->ensureDirectoryExists(static::$content.'/blueprints/collections/products');
        $files->put(static::$content.'/blueprints/collections/products/product.yaml', <<<'YAML'
title: Product
fields:
  - { handle: title, field: { type: text } }
  - { handle: price, field: { type: money } }
YAML);

        parent::setUp();

        Site::setSites(['default' => ['name' => 'Shop', 'url' => '/', 'locale' => 'en_GB', 'attributes' => ['currency' => 'GBP']]]);
        Blueprint::setDirectory(static::$content.'/blueprints');
    }

    protected function tearDown(): void
    {
        Cart::forgetCurrentCart();

        parent::tearDown();

        (new Filesystem)->deleteDirectory(static::$content);
    }

    /**
     * A mug, £8 by default.
     */
    protected function mug(int $price = 800): void
    {
        Entry::make()->collection('products')->blueprint('product')->id(self::MUG)->slug('mug')->data(['title' => 'Mug', 'price' => $price])->save();
    }

    /**
     * A basket with some mugs in it, made the visitor's.
     */
    protected function basket(int $mugs = 1): CartContract
    {
        $this->mug();
        $cart = Cart::make();
        $cart->lineItems()->create(['product' => self::MUG, 'quantity' => $mugs]);
        $cart->save();
        Cart::setCurrent($cart);

        return Cart::find($cart->id());
    }

    protected function discount(array $data, string $type = 'percentage_off'): \DuncanMcClean\Cargo\Contracts\Discounts\Discount
    {
        $discount = Discount::make()->title($data['title'] ?? 'Discount')->type($type)->data(collect($data)->except('title')->all());
        $discount->save();

        return $discount;
    }
}
