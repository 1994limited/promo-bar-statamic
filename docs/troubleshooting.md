# Troubleshooting

## A bar doesn't show

Check, in order:

1. Is it **On**, and is its status **Live** on the Promo Bars page? Scheduled and ended bars aren't in the page at all.
2. **Where**: does the page match? Patterns match the path in the address bar (`/shop/*` doesn't match `/shop` itself; `/shop*` does). On multisite, check **Sites**.
3. **Show to**: a bar for signed-in visitors needs them to be signed in, and in one of its groups if it lists any.
4. Have you dismissed it in this browser? Run `PromoBar.reset()` in the browser's console.
5. With **The first only**, a higher bar may be taking its place. Reorder, or show them stacked.
6. With a Cargo code or free shipping discount, is the discount still usable (dates, uses left)?

## A bar didn't start (or end) on time

On a statically cached site, pages are cleared when bars start and end by `promo-bar:tick`, on Laravel's scheduler. Check the scheduler runs (`php artisan schedule:list` should show `promo-bar:tick`), or run `php please promo-bar:tick`. Ended bars also hide themselves in visitors' browsers, even on a page cached before they ended.

## Changes don't appear on a cached site

Saving a bar or the settings clears the pages concerned. If your static cache is cleared some other way (a CDN, a reverse proxy), clear that too. With `static_caching.invalidate` off, clear it yourself: `php please static:clear`.

## Signed-in bars show to the wrong people

On cached pages, who's signed in comes from the `promo_bar_audience` cookie, set on sign-in. If you sign people in some other way than Laravel's `Login` event, the cookie is set on the next page Laravel draws. Check it's not encrypted (Promo Bar excludes it from `EncryptCookies`; a custom cookie name must be set in `config/promo-bar.php`, not changed elsewhere).

## The page jumps or flickers when the bar appears

Make sure `{{ promo_bar }}` isn't inside `{{ nocache }}` and isn't loaded with JavaScript. If a Content Security Policy blocks inline scripts, pass the `nonce` parameter (or use Laravel's `Vite::useCspNonce()`), so the small script after the bars can run before the first paint.

## The bar looks wrong

If your site styles plain `button` or `a` elements with `!important`, or sets everything with `* { … }`, those rules can reach into the bars. Use **None** and style the bars yourself, or override the [custom properties](styles.md#changing-a-preset-from-your-css).

## The Copy button doesn't appear

It needs JavaScript and a secure page (HTTPS, or `localhost`). Without it, the code can still be selected and copied by hand.
