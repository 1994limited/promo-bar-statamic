<?php

namespace NineteenNinetyFour\PromoBar\Database;

use Illuminate\Database\Eloquent\Model;

class BarModel extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected $casts = [
        'data' => 'array',
        'order' => 'integer',
    ];

    public function getTable()
    {
        return config('promo-bar.drivers.database.table', 'promo_bars');
    }

    public function getConnectionName()
    {
        return config('promo-bar.drivers.database.connection') ?: parent::getConnectionName();
    }
}
