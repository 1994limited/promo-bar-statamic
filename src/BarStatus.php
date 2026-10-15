<?php

namespace NineteenNinetyFour\PromoBar;

enum BarStatus: string
{
    case Live = 'live';
    case Scheduled = 'scheduled';
    case Expired = 'expired';
    case Disabled = 'disabled';

    public function label(): string
    {
        return match ($this) {
            self::Live => __('Live'),
            self::Scheduled => __('Scheduled'),
            self::Expired => __('Ended'),
            self::Disabled => __('Off'),
        };
    }
}
