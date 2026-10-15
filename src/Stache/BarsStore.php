<?php

namespace NineteenNinetyFour\PromoBar\Stache;

use Illuminate\Support\Facades\Log;
use NineteenNinetyFour\PromoBar\Bar;
use Statamic\Facades\YAML;
use Statamic\Stache\Stores\BasicStore;
use Statamic\Yaml\ParseException;

/**
 * The file driver's bars, one YAML file each, named by the bar's ID.
 */
class BarsStore extends BasicStore
{
    public function key()
    {
        return 'promo-bars';
    }

    public function makeItemFromFile($path, $contents)
    {
        try {
            $data = YAML::file($path)->parse($contents);
        } catch (ParseException) {
            Log::warning('Could not parse promo bar file: '.$path);
            $data = [];
        }

        return Bar::fromArray(pathinfo($path, PATHINFO_FILENAME), is_array($data) ? $data : [])->initialPath($path);
    }
}
