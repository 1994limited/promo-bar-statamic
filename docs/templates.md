# Templates

## The tag

Put `{{ promo_bar }}` where the bars should appear, usually straight after `<body>`:

```antlers
<body>
    {{ promo_bar }}

    <header>…</header>
```

<details>
<summary>Blade</summary>

```blade
<body>
    <s:promo_bar />

    <header>…</header>
```

</details>

It prints the bars that are live on the current page, highest first, and nothing at all when there aren't any. The page is found from the template's own `id` (the entry being shown) or, failing that, from the URL, so it works in layouts, partials and Blade alike.

## One bar or several

When several bars are live on a page, the settings decide what shows (**Tools → Addons → Promo Bar → Settings → General**). A template can choose otherwise with `mode`:

| `mode` | What shows |
| --- | --- |
| `first` | The highest bar only. When a visitor dismisses it, the next one takes its place. |
| `stack` | Every bar, one under another, in order. |
| `rotate` | One at a time, changing every few seconds, with previous, pause and next buttons. They share one space as tall as the tallest, so the page doesn't move. |

```antlers
{{ promo_bar mode="rotate" }}
```

<details>
<summary>Blade</summary>

```blade
<s:promo_bar mode="rotate" />
```

</details>

Rotating pauses while the pointer or keyboard focus is on the bar, and doesn't move on its own for visitors who prefer reduced motion. Without JavaScript, the first bar shows.

## Parameters

| Parameter | |
| --- | --- |
| `mode` | `first`, `stack` or `rotate`. Default: the settings' choice. |
| `limit` | At most this many bars, counted after those the visitor can't see (dismissed, or not for them) are left out. |
| `sticky` | `true` to keep the bars at the top of the window on scroll (`position: sticky`). Default: the settings' choice. |
| `class` | Classes added to the bars' wrapper. |
| `label` | The name screen readers announce for the bars' region. Default: "Announcements", or the settings' wording. |
| `assets` | `false` to leave out the stylesheet and script, when `{{ promo_bar:assets }}` is in `<head>`. |
| `nonce` | A Content Security Policy nonce for the script tags. Laravel's `Vite::useCspNonce()` is used if this isn't given. |

```antlers
{{ promo_bar mode="stack" limit="2" sticky="true" class="site-promos" }}
```

<details>
<summary>Blade</summary>

```blade
<s:promo_bar mode="stack" limit="2" sticky="true" class="site-promos" />
```

</details>

## The stylesheet and script in `<head>`

By default the first `{{ promo_bar }}` prints a `<link>` to the stylesheet, the script's settings and a deferred `<script>`. To load them earlier, put `{{ promo_bar:assets }}` in `<head>`; `{{ promo_bar }}` then prints only the bars.

```antlers
<head>
    …
    {{ promo_bar:assets }}
</head>
<body>
    {{ promo_bar }}
```

<details>
<summary>Blade</summary>

```blade
<head>
    …
    <s:promo_bar:assets />
</head>
<body>
    <s:promo_bar />
```

</details>

Both files are served by your site at `/!/promo-bar/…` with a version in the URL, so browsers keep them until they change. They're printed once a page, however many times the tags are used.

A very small script is also printed straight after the bars. It runs before the browser first paints them, and hides what this visitor has dismissed or isn't meant to see, so nothing flickers or jumps.

## Blade

The tags work as Statamic's Blade components, as above, or with `Statamic::tag()`:

```blade
{!! Statamic::tag('promo_bar')->param('mode', 'stack')->fetch() !!}
```

## Changing the markup

To change the HTML, publish the view:

```bash
php artisan vendor:publish --tag=promo-bar-views
```

It's copied to `resources/views/vendor/promo-bar/bars.blade.php`. Keep the `data-promo-bar-*` attributes: the script reads them. For colours and spacing, you don't need to: see [Styles](styles.md).

## Phones

On screens up to the phone width set under **Settings → General → Phones** (640px by default), bars are smaller, and each bar's phone options apply: its phone message, the parts it leaves out, or not showing at all. With `mode="stack"`, only the first bar shows on phones (unless that's turned off in the settings). See [Making bars](control-panel.md#phones).

## Without JavaScript

Without the script, bars still show (the first, or all of them stacked), with their message, button, code and end date. Visitors can't dismiss them, codes have no Copy button (the code can still be selected), bars for signed-in visitors or guests only stay hidden on cached pages, and free delivery shows the bar's message rather than the basket's progress.
