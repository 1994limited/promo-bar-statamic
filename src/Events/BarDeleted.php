<?php

namespace NineteenNinetyFour\PromoBar\Events;

use Illuminate\Foundation\Events\Dispatchable;
use NineteenNinetyFour\PromoBar\Bar;

class BarDeleted
{
    use Dispatchable;

    public function __construct(public Bar $bar) {}
}
