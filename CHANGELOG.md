# Changelog

## 1.1.0 (unreleased)

### Added
- **Phones.** Bars are smaller and tighter on phones, with the countdown in its two largest units ("4d 01h"). Each bar can have a shorter **phone message**, leave out its **countdown, code or button** on phones, or **not show on phones** at all. Stacked bars show only the first on phones. The phone width (640px) and the stacked-bars option are in **Settings → General → Phones**. It's all CSS, so it works on cached pages and without JavaScript.
- **A Desktop / Phone switch** on the Control Panel's previews.


## 1.0.0 (2026-10-15)

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
- Needs Statamic 6.35 or later.
