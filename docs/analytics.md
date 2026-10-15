# Analytics

Promo Bar can send what visitors do with bars to the analytics already on your site. It calls their scripts in the browser, so when your consent banner holds those scripts back, nothing is tracked through them. Nothing is sent from your server, and Promo Bar sets no tracking cookies of its own.

The one exception is a custom **Also send to a URL**: Promo Bar sends to it directly, consent or not. Use it only for a URL your consent setup allows (or one that collects nothing personal), and remember the events include the page's path.

Set it up under **Tools → Addons → Promo Bar → Settings → Analytics**.

## Events

| Event | When | Default name |
| --- | --- | --- |
| View | A bar is shown (once a visit for each bar, also when rotating to it). Off by default: it's sent on every page, and counts against Plausible's and Fathom's event allowances. | `promo_bar_view` |
| Click | The bar's button is clicked (including middle and ⌘-clicks). | `promo_bar_click` |
| Dismiss | A visitor closes a bar. | `promo_bar_dismiss` |
| Copy | A visitor copies the code. | `promo_bar_copy` |

Each name can be changed. Every event carries the bar's ID and name (the **Name** you gave it), the action, the button's URL, the site's handle and the page's path.

## Where they go

Tick any number:

| Tracker | What's sent |
| --- | --- |
| Google Tag Manager | `dataLayer.push({ event, promo_bar_id, promo_bar_name, promo_bar_action, promo_bar_url })`. In GTM, make a Custom Event trigger for the event name and Data Layer Variables for the rest. |
| Google Analytics 4 | `gtag('event', name, { promo_bar_id, promo_bar_name, promo_bar_action, promo_bar_url })`. Register them as custom dimensions to report on them. |
| Plausible | `plausible(name, { props: { bar, action, url } })`. Add the event names as custom event goals. |
| Fathom | `fathom.trackEvent('name: bar name')`. |
| Umami | `umami.track(name, { bar, action, url })`. |
| Matomo | `_paq.push(['trackEvent', 'Promo Bar', name, bar name])`. |
| Custom | See below. |

With Plausible, the button also carries Plausible's own tagged-event classes, so clicks are counted even before Promo Bar's script loads. The script removes them when it runs, so nothing is counted twice.

**Log events to the browser console** prints each event as it's sent, to check your setup. Turn it off when you're done.

## Custom

For any other tracker, or your own:

- **Function**: the name of a function already on your pages, such as `myTracker.track` or `_paq.push`. Only a name: no code can be entered.
- **Called with**:
  - `(event, { id, name, action, url, site, page })`, the default;
  - `(event, bar name, button URL)`;
  - `(['trackEvent', 'Promo Bar', event, bar name])`, the shape Matomo's `_paq.push` takes.
- **Rename properties**: send `id` as `campaign_id`, and so on (for the first shape).
- **Also send to a URL**: each event is POSTed there as JSON (`{ "event": "promo_bar_click", "id": "…", … }`) with the browser's `sendBeacon`. A path on your own site, or a full `https://` URL. This is sent whatever your consent banner says (see above).

The function is looked up when each event happens, so it can be defined after Promo Bar's script loads.

## In your own code

Every event is also a DOM event on `document`, whatever's ticked above:

```js
document.addEventListener('promo-bar:click', (event) => {
    console.log(event.detail.name, event.detail.url);
});
```

See [JavaScript](javascript.md#events). For a GTM click trigger without any of this, every bar and button has `data-promo-bar-id` and `data-promo-bar-name` attributes to match on.
