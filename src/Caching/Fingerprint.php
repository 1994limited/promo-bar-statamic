<?php

namespace NineteenNinetyFour\PromoBar\Caching;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use NineteenNinetyFour\PromoBar\PromoBars;

/**
 * Which bars are live, remembered between scheduler runs, so cached pages
 * are cleared the minute a bar starts or ends (and only then).
 */
class Fingerprint
{
    public const KEY = 'promo-bar::live';

    public function __construct(protected PromoBars $bars, protected Invalidator $invalidator) {}

    /**
     * Compare with the last run and clear the pages of bars that started
     * or ended since.
     *
     * @return array<int, string> The IDs of the bars that changed.
     */
    public function check(?CarbonImmutable $now = null): array
    {
        $live = $this->bars->liveIds($now);
        $previous = Cache::get(self::KEY);

        Cache::forever(self::KEY, $live);

        if (! is_array($previous)) {
            return [];
        }

        $changed = array_values(array_unique([...array_diff($live, $previous), ...array_diff($previous, $live)]));

        if ($changed !== []) {
            $this->invalidator->bars(array_map(fn ($id) => $this->bars->find($id), $changed));
        }

        return $changed;
    }

    /**
     * Note the current live bars without clearing anything, as after a
     * save (which clears what it needs itself).
     */
    public function remember(?CarbonImmutable $now = null): void
    {
        Cache::forever(self::KEY, $this->bars->liveIds($now));
    }
}
