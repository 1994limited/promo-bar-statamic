<?php

namespace NineteenNinetyFour\PromoBar\Integrations;

use Carbon\CarbonImmutable;
use Composer\InstalledVersions;
use DuncanMcClean\Cargo\Contracts\Discounts\Discount;
use DuncanMcClean\Cargo\Facades\Cart;
use DuncanMcClean\Cargo\Facades\Discount as Discounts;
use DuncanMcClean\Cargo\ServiceProvider;
use DuncanMcClean\Cargo\Support\Money;
use NineteenNinetyFour\PromoBar\Bar;
use Statamic\Facades\Site;
use Throwable;

/**
 * Promo Bar with Cargo (duncanmcclean/statamic-cargo): discount codes taken
 * from Cargo's discounts, and how far the basket is from free delivery. It
 * all switches off when Cargo isn't installed.
 */
class Cargo
{
    /** The Cargo releases this works with: 1.15 up to (not including) 2.0. */
    public const MINIMUM_VERSION = '1.15.0';

    public const BEFORE_VERSION = '2.0.0';

    protected ?bool $supported = null;

    public function installed(): bool
    {
        return class_exists(ServiceProvider::class)
            && app()->providerIsLoaded(ServiceProvider::class)
            && $this->supported();
    }

    /**
     * Whether the installed Cargo is a version this was built for. An older
     * or newer major one is left alone, so the site carries on without the
     * integration rather than failing.
     */
    public function supported(?string $version = null): bool
    {
        if ($version === null && $this->supported !== null) {
            return $this->supported;
        }

        $version ??= class_exists(InstalledVersions::class) && InstalledVersions::isInstalled('duncanmcclean/statamic-cargo')
            ? InstalledVersions::getVersion('duncanmcclean/statamic-cargo')
            : null;

        $supported = class_exists(Discounts::class) && (
            // A development checkout: trust it if the classes are there.
            $version === null || ! preg_match('/^\d/', $version)
            || (version_compare($version, self::MINIMUM_VERSION, '>=') && version_compare($version, self::BEFORE_VERSION, '<'))
        );

        return func_num_args() > 0 ? $supported : $this->supported = $supported;
    }

    /**
     * Discounts with a code, for the bar's code picker.
     *
     * @return array<string, string> ID => label
     */
    public function codeOptions(): array
    {
        if (! $this->installed()) {
            return [];
        }

        return $this->discounts()
            ->filter(fn (Discount $discount) => filled($discount->get('discount_code')))
            ->mapWithKeys(fn (Discount $discount) => [$discount->id() => $discount->get('discount_code').' – '.$discount->title()])
            ->all();
    }

    /**
     * Free shipping discounts with no code (applied automatically) and a
     * minimum order value, for the free delivery threshold.
     *
     * @return array<string, string> ID => label
     */
    public function freeShippingOptions(): array
    {
        if (! $this->installed()) {
            return [];
        }

        return $this->discounts()
            ->filter(fn (Discount $discount) => $this->isFreeShipping($discount) && blank($discount->get('discount_code')) && (int) $discount->get('minimum_order_value') > 0)
            ->mapWithKeys(fn (Discount $discount) => [$discount->id() => $discount->title()])
            ->all();
    }

    public function discount(?string $id): ?Discount
    {
        if (! $this->installed() || blank($id)) {
            return null;
        }

        try {
            return Discounts::find($id);
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Whether a discount can be used now: it exists and is within its
     * dates (Cargo's dates are whole days), and isn't used up.
     */
    public function usable(?Discount $discount, ?CarbonImmutable $now = null): bool
    {
        if (! $discount) {
            return false;
        }

        $now ??= CarbonImmutable::now();

        if ($start = Bar::date($discount->get('start_date'))) {
            if ($start->startOfDay()->greaterThan($now)) {
                return false;
            }
        }

        if ($end = Bar::date($discount->get('end_date'))) {
            if ($end->endOfDay()->lessThan($now)) {
                return false;
            }
        }

        $maximum = $discount->get('maximum_uses');

        return $maximum === null || (int) $discount->get('redemptions_count') < (int) $maximum;
    }

    /**
     * The code a bar shows from a Cargo discount, if it can be used now.
     */
    public function code(Bar $bar, ?CarbonImmutable $now = null): ?string
    {
        $discount = $this->discount($bar->get('cargo_discount'));

        return $this->usable($discount, $now) ? (string) $discount->get('discount_code') : null;
    }

    /**
     * The basket total that gets free delivery, in the currency's smallest
     * unit: the bar's own amount, or a free shipping discount's minimum
     * order value. Null when there isn't one (or the discount has ended).
     */
    public function threshold(Bar $bar, ?CarbonImmutable $now = null): ?int
    {
        if (! $this->installed()) {
            return null;
        }

        if ($bar->get('free_delivery_source', 'amount') === 'discount') {
            $discount = $this->discount($bar->get('free_delivery_discount'));

            return $this->usable($discount, $now) && (int) $discount->get('minimum_order_value') > 0
                ? (int) $discount->get('minimum_order_value')
                : null;
        }

        $amount = $bar->get('free_delivery_threshold');

        return is_numeric($amount) && (int) $amount > 0 ? (int) $amount : null;
    }

    /**
     * The visitor's basket subtotal, as Cargo checks a discount's minimum
     * order value against it. Null when they have no basket (one isn't
     * made just to ask).
     */
    public function subtotal(): ?int
    {
        if (! $this->installed()) {
            return null;
        }

        try {
            return Cart::hasCurrentCart() ? (int) Cart::current()->subTotal() : null;
        } catch (Throwable) {
            return null;
        }
    }

    public function money(int $amount): string
    {
        return Money::format($amount, Site::current());
    }

    protected function discounts()
    {
        try {
            return Discounts::all();
        } catch (Throwable) {
            return collect();
        }
    }

    protected function isFreeShipping(Discount $discount): bool
    {
        return $discount->type() === 'free_shipping';
    }
}
