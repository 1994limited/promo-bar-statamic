<?php

namespace NineteenNinetyFour\PromoBar\Commands;

use Illuminate\Console\Command;
use NineteenNinetyFour\PromoBar\Caching\Fingerprint;
use NineteenNinetyFour\PromoBar\Caching\Invalidator;
use Statamic\Console\RunsInPlease;

/**
 * Run every minute by Laravel's scheduler: clears statically cached pages
 * when a bar starts or ends.
 */
class Tick extends Command
{
    use RunsInPlease;

    protected $signature = 'promo-bar:tick';

    protected $description = 'Clear cached pages when a promo bar starts or ends';

    public function handle(Fingerprint $fingerprint, Invalidator $invalidator): int
    {
        if (! $invalidator->enabled()) {
            $fingerprint->remember();

            return self::SUCCESS;
        }

        $changed = $fingerprint->check();

        if ($changed !== []) {
            $this->components->info(count($changed) === 1 ? 'A bar started or ended: its pages were cleared.' : count($changed).' bars started or ended: their pages were cleared.');
        }

        return self::SUCCESS;
    }
}
