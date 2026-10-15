<?php

namespace NineteenNinetyFour\PromoBar;

use Illuminate\Http\Request;
use Statamic\Contracts\Entries\Entry as EntryContract;
use Statamic\Facades\Entry;
use Statamic\Facades\Site;
use Throwable;

/**
 * The page a bar might show on: its site, its path, and the entry it is
 * (if it's one).
 */
class Page
{
    public function __construct(
        public readonly string $site,
        public readonly string $path,
        public readonly ?string $entry = null,
        public readonly ?string $collection = null,
    ) {}

    /**
     * The page being rendered: the entry from the template's context if
     * there is one, otherwise the entry at the request's URL.
     */
    public static function current(?Request $request = null, mixed $entry = null): static
    {
        $request ??= request();
        $site = Site::current()->handle();
        $path = static::normalise($request->getPathInfo());

        if (! $entry instanceof EntryContract) {
            $entry = is_string($entry) && $entry !== '' ? static::find($entry) : null;
        }

        $entry ??= static::findByUri($request, $site);

        return new static($site, $path, $entry?->id(), $entry?->collectionHandle());
    }

    public static function normalise(string $path): string
    {
        $path = parse_url($path, PHP_URL_PATH) ?: '/';
        $path = '/'.trim(rawurldecode($path), '/');

        return mb_strtolower($path);
    }

    protected static function find(string $id): ?EntryContract
    {
        try {
            return Entry::find($id);
        } catch (Throwable) {
            return null;
        }
    }

    protected static function findByUri(Request $request, string $site): ?EntryContract
    {
        try {
            $sitePath = static::normalise((string) parse_url(Site::get($site)?->absoluteUrl() ?? '', PHP_URL_PATH));
            $path = static::normalise($request->getPathInfo());

            if ($sitePath !== '/' && str_starts_with($path, $sitePath)) {
                $path = '/'.ltrim(substr($path, strlen($sitePath)), '/');
            }

            return Entry::findByUri($path, $site);
        } catch (Throwable) {
            return null;
        }
    }
}
