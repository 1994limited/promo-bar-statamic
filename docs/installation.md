# Installation

## Requirements

- PHP 8.3 or later.
- Statamic 6.35 or later. Control Panel permissions, and more than one user, need Statamic Pro. Bars work without it.
- For free delivery progress and codes from discounts: [Cargo](https://builtwithcargo.dev) 1.15 or later (optional).

## Installing

```bash
composer require 1994/promo-bar-statamic
```

Add the tag to your layout, straight after `<body>`, where the bars should appear:

```antlers
<body>
    {{ promo_bar }}
    …
```

<details>
<summary>Blade</summary>

```blade
<body>
    <s:promo_bar />
    …
```

</details>

The first `{{ promo_bar }}` on a page also prints the stylesheet and the script, so nothing else is needed. To load them in `<head>` instead, see [Templates](templates.md#the-stylesheet-and-script-in-head).

Then make your first bar under **Content → Promo Bars → Create Bar**. See [Making bars](control-panel.md).

## The scheduler

Bars start and end on time on their own when pages aren't cached. With Statamic's static caching on, cached pages are cleared when a bar starts or ends by `promo-bar:tick`, which the addon schedules to run every minute. Make sure Laravel's scheduler runs on your server (most hosts have a setting for it, or add this cron entry):

```bash
* * * * * cd /path-to-your-site && php artisan schedule:run >> /dev/null 2>&1
```

Without the scheduler, a bar that has ended still hides itself in visitors' browsers, but one that is starting won't appear until the cache is next cleared. See [Static caching](caching.md).

## Where bars are kept

Bars are YAML files in `content/promo-bars`, one per bar, so they're committed with the rest of your content. To keep them in the database instead, see [Configuration](configuration.md#storage).

## Licensing

Promo Bar is free to try on local and staging sites. Buy a licence on the [Statamic Marketplace](https://statamic.com/marketplace) for each live site.

## Updating

```bash
composer update 1994/promo-bar-statamic
```

Read the [changelog](../CHANGELOG.md) first. If you've published the bar view (`resources/views/vendor/promo-bar/bars.blade.php`), compare it with the new one.
