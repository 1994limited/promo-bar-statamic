<?php

namespace NineteenNinetyFour\PromoBar\Caching;

use NineteenNinetyFour\PromoBar\Bar;
use NineteenNinetyFour\PromoBar\Renderer;
use NineteenNinetyFour\PromoBar\Targeting;
use Statamic\Facades\Entry;
use Statamic\Facades\Site;
use Statamic\Facades\StaticCache;
use Statamic\StaticCaching\Cacher;
use Throwable;

/**
 * Clears statically cached pages when the bars on them change: when a bar
 * is saved, deleted or reordered, when it starts or ends, and when the
 * settings change. A bar listed for a few entries or exact URLs clears
 * only those pages; anything wider clears the whole cache.
 */
class Invalidator
{
    public function enabled(): bool
    {
        return (bool) config('promo-bar.static_caching.invalidate', true) && Renderer::pagesMayBeCached();
    }

    /**
     * After bars change. Pass each bar as it is now and as it was, as both
     * the pages it showed on and the pages it shows on now need clearing.
     *
     * @param  iterable<Bar|null>  $bars
     */
    public function bars(iterable $bars): void
    {
        if (! $this->enabled()) {
            return;
        }

        $urls = [];

        foreach ($bars as $bar) {
            if (! $bar) {
                continue;
            }

            $barUrls = $this->urls($bar);

            if ($barUrls === null) {
                $this->flush();

                return;
            }

            array_push($urls, ...$barUrls);
        }

        $urls = array_values(array_unique($urls));

        if ($urls !== []) {
            try {
                app(Cacher::class)->invalidateUrls($urls);
            } catch (Throwable $e) {
                report($e);
                $this->flush();
            }
        }
    }

    public function flush(): void
    {
        if (! $this->enabled()) {
            return;
        }

        try {
            StaticCache::flush();
        } catch (Throwable $e) {
            report($e);
        }
    }

    /**
     * The pages a bar can show on, as URLs (with * for wildcards), or null
     * when that's everywhere or can't be listed (whole collections,
     * "everywhere except").
     *
     * @return array<int, string>|null
     */
    public function urls(Bar $bar): ?array
    {
        if ($bar->get('pages', 'everywhere') !== 'only' || Targeting::collections($bar) !== []) {
            return null;
        }

        $urls = [];

        foreach (Targeting::entries($bar) as $id) {
            try {
                $entry = Entry::find($id);
            } catch (Throwable) {
                $entry = null;
            }

            // The entry and every translation of it, as the bar shows on all.
            $root = $entry && method_exists($entry, 'root') ? ($entry->root() ?? $entry) : $entry;

            // Queried directly: Entry::descendants() is remembered for the
            // request, so can miss a translation made in the same one.
            $translations = $root ? Entry::query()->where('collection', $root->collectionHandle())->where('origin', $root->id())->get()->all() : [];

            foreach ($root ? [$root, ...$translations] : [] as $localization) {
                if ($bar->sites() !== [] && ! in_array($localization->locale(), $bar->sites(), true)) {
                    continue;
                }

                if ($url = $localization->absoluteUrl()) {
                    $urls[] = $url;
                }
            }
        }

        $patterns = Targeting::patterns($bar);

        if ($patterns !== []) {
            $sites = $bar->sites() ?: Site::all()->map->handle()->all();

            foreach ($sites as $handle) {
                $site = Site::get($handle);

                if (! $site) {
                    continue;
                }

                $parts = parse_url($site->absoluteUrl());
                $origin = ($parts['scheme'] ?? 'https').'://'.($parts['host'] ?? '').(isset($parts['port']) ? ':'.$parts['port'] : '');

                foreach ($patterns as $pattern) {
                    $urls[] = $origin.$pattern;
                }
            }
        }

        return $urls;
    }
}
