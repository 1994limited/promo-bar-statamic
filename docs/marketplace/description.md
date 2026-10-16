**Announcement and promo bars for your site's marketing: a sale with a countdown and a discount code, free delivery once the basket reaches £40, early access for members. You make them in the Control Panel, and they show and hide when their dates say.**

Bars are drawn on the server, so the page doesn't jump, they work without JavaScript, and search engines see them. They're safe behind full static caching: anything that depends on the visitor (signed in or not, what they've dismissed, their basket) is settled in their browser.

## As many bars as you like

Each has a message (bold, italic, links), an optional button, and a style. Drag them into order: when several are live, the top one wins, or show them all stacked, or rotate them.

## Scheduled

A start and an end, to the minute. Ended and upcoming bars are never in the page, so a Black Friday code can't leak early.

## Targeted

Everywhere, only on some pages, or everywhere except some: by entry, by collection, or by URL pattern (`/shop/*`). On multisite, by site. And for everyone, guests only, or signed-in visitors, optionally in certain user groups.

## Countdowns and codes

A live countdown to the end, and a discount code with a Copy button. Without JavaScript, the end date and the code still show.

## Made for phones

Bars are smaller on phones. Give a bar a shorter phone message, and leave out its countdown, code or button there. Stacked bars show only the first. It's all CSS, so nothing jumps, even on cached pages.

## Dismissible

Visitors can close a bar. It stays closed in their browser, comes back after a number of days if you like, and reappears when you change what it says.

## Styled in the Control Panel

Six presets (Solid, Subtle, Dark, Outline, Gradient, Minimal), each with its colours, button, size and alignment editable, a live preview, and a warning when text is hard to read. Give one bar its own colours, or choose None and bring your own CSS.

## Your analytics

Clicks, dismissals, code copies and (if you like) views go to Google Tag Manager, GA4, Plausible, Fathom, Umami, Matomo, or a function of your own, through the trackers already on your pages. No cookies, nothing sent from your server.

## Cargo shops

With [Cargo](https://builtwithcargo.dev), show how far the basket is from free delivery ("£12.00 away from free delivery") with a progress bar, and take discount codes straight from Cargo's discounts. Bars end when their discount does.

## Accessible

A labelled region, real buttons with clear names, focus kept in place, a pause button when rotating, and no movement for visitors who prefer reduced motion.

## One tag

`{{ promo_bar }}` in your layout, or `<s:promo_bar />` in Blade.

## Requirements

- Statamic 6.35 or later
- PHP 8.3 or later
- **Statamic Pro** for Control Panel permissions and more than one user. Bars work without it.
- Optional: Cargo 1.15 or later, for free delivery progress and Cargo codes
