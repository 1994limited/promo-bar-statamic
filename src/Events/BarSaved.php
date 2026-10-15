<?php

namespace NineteenNinetyFour\PromoBar\Events;

use Illuminate\Foundation\Events\Dispatchable;
use NineteenNinetyFour\PromoBar\Bar;

class BarSaved
{
    use Dispatchable;

    /**
     * @param  Bar|null  $original  The bar as it was before, or null when it's new.
     */
    public function __construct(public Bar $bar, public ?Bar $original = null) {}
}
