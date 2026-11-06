# Making bars

![Content → Promo Bars: five bars with their status, dates, where they show and style](https://raw.githubusercontent.com/1994limited/statamic-marketplace/main/promo-bar/charts/cp-bars.png)

Bars live under **Content → Promo Bars**. The list shows each bar's status (Live, Scheduled, Ended or Off), its dates, where it shows and its style. Switch a bar on or off from the list, and use each row's menu to edit, duplicate, move or delete it.

## Order

When more than one bar is live on a page, their order decides what shows: the top one only, all of them stacked in this order, or one at a time starting from the top (see [Templates](templates.md#one-bar-or-several)). Drag rows to reorder them, or use **Move up** and **Move down** in a row's menu.

## A bar's fields

The form has a live preview at the top, in the bar's style.

![Editing the Autumn sale bar, with its preview](https://raw.githubusercontent.com/1994limited/statamic-marketplace/main/promo-bar/charts/cp-bar.png)

### Content

- **Name**: for you. It's shown in the Control Panel and sent to your analytics; visitors don't see it.
- **On**: off hides the bar everywhere, whatever its dates.
- **Message**: a line or two, with bold, italic and links (web, `mailto:` and `tel:` links only). With free delivery on (Cargo), `:threshold` is replaced with the amount.
- **Button**: a label and a link (a URL or an entry), and whether it opens in a new tab. Leave the label empty for no button. Only web, `mailto:` and `tel:` links are used.
- **Count down to the end**: a live countdown to the end date (set under Schedule). Without JavaScript, the end date shows instead. The countdown's label can be changed for this bar.
- **Discount code**: none, a code you type, or (with Cargo) a Cargo discount's code. It shows with a Copy button.
- **Free delivery** (with Cargo): see [Cargo](cargo.md).
- **On phones**: phones get a smaller bar (see [Phones](#phones)). Turn off **Show on phones** for a bar that's only for bigger screens, give it a shorter **Phone message**, and choose whether its **Countdown**, **Code** and **Button** show on phones.
- **Visitors can dismiss it**: a close button. A dismissed bar stays hidden in that visitor's browser, comes back after the number of days you set (or never, if empty), and comes back anyway when you change its message, button, code or end date.

### Schedule

- **Starts** and **Ends**, to the minute, in your site's timezone (`APP_TIMEZONE`). Leave both empty to show the bar until you switch it off. A bar that hasn't started or has ended isn't in the page at all.

### Where

![The Where tab: only on the products collection, /shop and /basket](https://raw.githubusercontent.com/1994limited/statamic-marketplace/main/promo-bar/charts/cp-where.png)

- **Sites** (multisite only): leave empty for every site.
- **Pages**: everywhere, only on some pages, or everywhere except some. "Some" is any of:
  - **Entries**: those entries' pages, and their translations in other sites.
  - **Collections**: every entry in them.
  - **URLs**: one per line, as in the address bar, with `*` for "anything". `/shop/*` is every page under `/shop/`; `/shop*` also matches `/shop` and `/shopping`; `*/sale` is any page ending `/sale`. Case and a trailing slash don't matter; query strings are ignored.
- **Show to**: everyone, guests only, or signed-in visitors. Signed-in can be narrowed to user groups.

Who's signed in is worked out in the visitor's browser (from a small cookie) so it works on cached pages. That decides what's shown, not who's allowed to see it: anything in a bar is in the page's HTML, so don't put anything private in one. See [Static caching](caching.md#signed-in-and-guest-bars).

### Style

- **Style**: one of the presets, or the default chosen in the settings. See [Styles](styles.md).
- **Own colours**: change this bar's background, text and button colours without changing the preset.

## Phones

A bar that fits one line on a laptop can take four on a phone. On screens up to the phone width in the settings (640px unless you change it), bars are smaller and tighter, the countdown shows its two largest units ("4d 01h"), and "Use code" is left to screen readers. On top of that, each bar can:

- **Use a phone message**: "**Autumn sale:** 20% off" in place of "**Autumn sale:** 20% off every bag of beans". `:threshold` works here too.
- **Leave out its countdown, code or button.** A sale bar might keep its button and leave the code to the page it links to.
- **Not show on phones at all.**

With bars stacked, phones show only the first one the visitor can see (turn this off under **Settings → General → Phones**). Rotating bars already show one at a time.

The form's preview has a **Desktop / Phone** switch, to see each.

![The Autumn sale bar on a phone: its phone message, a short countdown, the code and the button on two lines](https://raw.githubusercontent.com/1994limited/statamic-marketplace/main/promo-bar/charts/shop-mobile.png)

Both messages are in the page, and the stylesheet picks which shows, so it works on cached pages and without JavaScript, and nothing jumps. Screen readers read only the one shown.

## Settings

**Tools → Addons → Promo Bar → Settings** (or **Settings** on the Promo Bars page) has how several bars show, the presets, the wording, and analytics. See [Configuration](configuration.md#the-settings-page).

## Permissions

With Statamic Pro, give roles:

- **View promo bars**: see the list and open bars, read only.
- **Create, edit, reorder and delete promo bars**: everything else on the Promo Bars page.

The settings page uses Statamic's own permission to edit addon settings.
