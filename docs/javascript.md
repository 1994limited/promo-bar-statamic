# JavaScript

The script has no dependencies and needs no build step. It's loaded (deferred) with the first `{{ promo_bar }}` or by `{{ promo_bar:assets }}`.

## `window.PromoBar`

| | |
| --- | --- |
| `PromoBar.refresh()` | Settle every bar again and fetch the free delivery progress. Call it after the basket changes without a page load. Returns a promise. |
| `PromoBar.reset()` | Show every dismissed bar again (in this browser). |
| `PromoBar.start()` | Set up bars added to the page after it loaded. |

## Events

Dispatched on `document`, with the bar in `event.detail`:

| Event | When |
| --- | --- |
| `promo-bar:view` | A bar is shown, once a visit for each. |
| `promo-bar:click` | Its button is clicked. |
| `promo-bar:dismiss` | It's dismissed. |
| `promo-bar:copy` | Its code is copied. |

`event.detail` has `id`, `name`, `url` (the button's), `site`, `page` (the path) and `element` (the bar).

```js
document.addEventListener('promo-bar:dismiss', ({ detail }) => {
    console.log(`${detail.name} was dismissed on ${detail.page}`);
});
```

## What's kept in the browser

| Key | Where | |
| --- | --- | --- |
| `promo-bar:dismissed` | `localStorage` | Dismissed bars (by ID and a fingerprint of their content) and when each comes back. |
| `promo-bar:viewed` | `sessionStorage` | Bars already counted as viewed this visit. |
| `promo_bar_audience` | Cookie, from the server | Signed in, and user groups. See [Static caching](caching.md#signed-in-and-guest-bars). |

None of these identify the visitor.

## Attributes

If you [publish the view](templates.md#changing-the-markup), keep these:

| Attribute | On | |
| --- | --- | --- |
| `data-promo-bar-group` | The wrapper | With `data-mode`, `data-interval`, `data-phone-width` and `data-audience-cookie`. |
| `data-promo-bar` | Each bar | With `data-promo-bar-id`, `data-promo-bar-name`, `data-promo-bar-key` (for dismissing), and as needed `data-ends`, `data-audience`, `data-groups`, `data-dismiss-days`. |
| `data-promo-bar-js` | Things only the script can use | Hidden until it runs: `dismiss`, `copy`, `controls`, `countdown`. |
| `data-promo-bar-dismiss`, `-copy`, `-cta` | Buttons and the link | What a click does. |
| `data-promo-bar-previous`, `-next`, `-pause` | Rotating's buttons | |
| `data-promo-bar-countdown`, `-remaining`, `-fallback` | The countdown | `-fallback` (the end date) is kept for screen readers once the countdown runs. |
| `data-promo-bar-code` | The code | |
| `data-promo-bar-free-delivery`, `-progress-text`, `-message` | Free delivery | `data-fetch` when the browser should fetch it. |
| `data-promo-bar-status` | A live region | For "Copied". |

## The state endpoint

`GET /!/promo-bar/state?bars=id,id` returns each listed bar's free delivery progress for the visitor's basket, or `null` (no basket, or not a live free delivery bar). It's never cached and never makes a basket.

```json
{ "bars": { "3f1…": { "remaining": 1200, "remaining_formatted": "£12.00", "percent": 70, "unlocked": false, "text": "£12.00 away from free delivery" } } }
```
