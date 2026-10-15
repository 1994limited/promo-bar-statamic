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
     * Compare with the last check and clear the pages of bars that started
     * or ended since.
     *
     * With nothing to compare with (the first run, or after Laravel's cache
     * was cleared), cached pages may hold any bars, so they're all cleared
     * unless $flushWhenUnknown is false.
     *
     * @param  array<int, string>  $handled  Bars whose pages were just cleared some other way.
     * @return array<int, string> The IDs of the bars that changed.
     */
    public function check(?CarbonImmutable $now = null, bool $flushWhenUnknown = true, array $handled = []): array
    {
        $live = $this->bars->liveIds($now);
        $previous = Cache::get(self::KEY);

        if (! is_array($previous)) {
            // Without flushing, leave it unrecorded: the next tick flushes.
            if ($flushWhenUnknown) {
                $this->invalidator->flush();
                Cache::forever(self::KEY, $live);
            }

            return [];
        }

        Cache::forever(self::KEY, $live);

        $changed = array_values(array_unique([...array_diff($live, $previous), ...array_diff($previous, $live)]));
        $pending = array_values(array_diff($changed, $handled));

        if ($pending !== []) {
            $this->invalidator->bars(array_map(fn ($id) => $this->bars->find($id), $pending));
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
