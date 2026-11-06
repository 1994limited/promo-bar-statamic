# Static caching

Promo Bar is built for sites with Statamic's static caching on, half or full. A cached page is the same for every visitor, so the bars in it are too. What depends on the visitor is settled by the script in their browser.

## What's in the page

The page's HTML holds the bars live on that page when it was cached, in order, with their dates, audience and dismiss settings as `data-` attributes. Bars that are switched off, haven't started or have ended aren't in it at all (nor in the stylesheet), so nothing can be read before its time.

## What the browser settles

Straight after the bars, a few lines of script (before the first paint):

- hide bars this visitor has dismissed,
- hide bars for signed-in visitors from guests, and the other way round,
- hide bars whose end has passed since the page was cached,
- show the first bar that's left (or all of them, stacked, or the first of the rotation).

The main script (deferred) then runs countdowns, Copy buttons and rotating, and fetches free delivery progress from `/!/promo-bar/state`, which isn't cached.

## What clears cached pages

| When | What's cleared |
| --- | --- |
| A bar is saved or deleted | The pages it showed on and the pages it shows on now: only those pages when it lists entries or URLs; every page when it shows everywhere, everywhere except some, or in whole collections. |
| Bars are reordered | Every page. |
| The settings are saved | Every page. |
| A bar starts or ends (checked every minute by `promo-bar:tick`, and whenever a bar is saved) | That bar's pages, as above. Includes a bar ending because its Cargo discount ended. |
| The first tick after installing, or after Laravel's cache is cleared | Every page, once: until then, there's no record of which bars cached pages hold. |

`promo-bar:tick` runs on Laravel's scheduler: make sure it's running (see [Installation](installation.md#the-scheduler)). You can run it yourself to check:

```bash
php please promo-bar:tick
```

To clear the cache your own way, set `static_caching.invalidate` to `false` in `config/promo-bar.php`; to schedule the tick yourself, set `static_caching.schedule` to `false`. See [Configuration](configuration.md).

## Signed-in and guest bars

![The same cached page for a signed-in member: the members' bar instead of the newsletter](https://raw.githubusercontent.com/1994limited/statamic-marketplace/main/promo-bar/charts/member.png)

On cached pages, the script can't ask the server who's looking. Instead, when someone signs in Promo Bar sets a small cookie, `promo_bar_audience`: `1` for signed in, then their user groups (`1:members,trade`). It's removed when they sign out, and put right on any page Laravel draws (for people already signed in when you installed the addon, or whose groups have changed). It's only set on sites with a bar for signed-in visitors or guests.

The cookie isn't secret and doesn't sign anyone in: it only says which bars to show. Every bar on a page is in its HTML, whoever it's for, so **don't put anything private in a bar**.

Without static caching (`STATAMIC_STATIC_CACHING_STRATEGY=null`), all of this is worked out on the server: pages carry only the bars for that visitor, and the cookie isn't read.

## `nocache`

You don't need `{{ nocache }}` around `{{ promo_bar }}`, and it's better without: with full static caching, what's inside `nocache` is fetched after the page loads, so the bars would push the page down.
