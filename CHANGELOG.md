# Changelog

## 1.0.0 (unreleased)

### Added
- **Promo bars**, made under Content → Promo Bars: a message, a button, a countdown, a discount code, and a close button that's remembered. Drag them into order.
- **Scheduling** to the minute, with ended and upcoming bars kept out of the page and the stylesheet.
- **Targeting** by site, entry, collection and URL pattern (only on, or everywhere except), and by visitor: everyone, guests, or signed-in users in chosen groups.
- **First, stacked or rotating** when several bars are live, with pause, previous and next buttons when rotating.
- **Six style presets**, editable in the settings with a live preview and contrast warnings, a bar's own colours, and None for your own CSS.
- **Static caching**: bars are drawn on the server the same for everyone and settled in the browser; cached pages are cleared when bars change, start or end (`promo-bar:tick`).
- **Analytics** to Google Tag Manager, GA4, Plausible, Fathom, Umami, Matomo, or a function or URL of your own, and DOM events.
- **Cargo**: free delivery progress, and codes from Cargo's discounts.
- **File and database storage**, with `promo-bar:transfer` between them.
- `{{ promo_bar }}` and `{{ promo_bar:assets }}` tags, with Blade versions, and a publishable view.
