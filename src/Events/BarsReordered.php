<?php

namespace NineteenNinetyFour\PromoBar\Events;

use Illuminate\Foundation\Events\Dispatchable;

class BarsReordered
{
    use Dispatchable;

    /**
     * @param  array<int, string>  $ids  The bars' IDs, highest priority first.
     */
    public function __construct(public array $ids) {}
}
