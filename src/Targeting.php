<?php

namespace NineteenNinetyFour\PromoBar;

/**
 * Which pages a bar shows on: everywhere, only on some, or everywhere
 * except some. "Some" is any of the entries, collections and URL patterns
 * it lists.
 */
class Targeting
{
    public function matches(Bar $bar, Page $page): bool
    {
        if (! $bar->showsOnSite($page->site)) {
            return false;
        }

        return match ($bar->get('pages', 'everywhere')) {
            'only' => $this->listed($bar, $page),
            'except' => ! $this->listed($bar, $page),
            default => true,
        };
    }

    /**
     * Whether the page is one the bar lists.
     */
    public function listed(Bar $bar, Page $page): bool
    {
        if ($page->entry && in_array($page->entry, static::entries($bar), true)) {
            return true;
        }

        if ($page->collection && in_array($page->collection, static::collections($bar), true)) {
            return true;
        }

        foreach (static::patterns($bar) as $pattern) {
            if (static::matchesPattern($pattern, $page->path)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<int, string>
     */
    public static function entries(Bar $bar): array
    {
        return array_values(array_filter((array) $bar->get('entries', []), 'is_string'));
    }

    /**
     * @return array<int, string>
     */
    public static function collections(Bar $bar): array
    {
        return array_values(array_filter((array) $bar->get('collections', []), 'is_string'));
    }

    /**
     * The URL patterns, one per line, each a path with * for "anything".
     * Full URLs are read as their path.
     *
     * @return array<int, string>
     */
    public static function patterns(Bar $bar): array
    {
        $lines = preg_split('/\R/', (string) $bar->get('urls', '')) ?: [];

        return collect($lines)
            ->map(fn ($line) => trim($line))
            ->filter()
            ->map(function (string $line) {
                if (preg_match('#^https?://#i', $line)) {
                    $line = parse_url($line, PHP_URL_PATH) ?: '/';
                }

                return Page::normalise(str_replace('*', '__PROMO_BAR_STAR__', $line));
            })
            ->map(fn ($line) => str_replace('__promo_bar_star__', '*', $line))
            ->unique()
            ->values()
            ->all();
    }

    public static function matchesPattern(string $pattern, string $path): bool
    {
        $regex = '#^'.str_replace('\*', '.*', preg_quote($pattern, '#')).'$#u';

        return (bool) preg_match($regex, $path);
    }

    /**
     * Whether a pattern names one page (no wildcard), so only that page's
     * cache needs clearing.
     */
    public static function isExact(string $pattern): bool
    {
        return ! str_contains($pattern, '*');
    }
}
