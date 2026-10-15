# Configuration

Promo Bar has two places for settings: the **settings page** in the Control Panel, for everything editors might change, and **`config/promo-bar.php`**, for how the site is set up.

## The settings page

**Tools → Addons → Promo Bar → Settings**. Saving clears statically cached pages.

| Tab | |
| --- | --- |
| General | What shows when several bars are live (first, stacked or rotating) and the seconds each when rotating; the default style; sticking to the top on scroll; and how many days before a dismissed bar comes back (each bar can choose its own). |
| Styles | The six presets. See [Styles](styles.md). |
| Wording | The words bars use: the region's name for screen readers, Dismiss, the countdown and end date, "Use code", Copy and Copied, the rotating buttons, and the free delivery lines. Leave any empty for the default. |
| Analytics | See [Analytics](analytics.md). |

They're saved in `resources/addons/promo-bar-statamic.yaml` (or the database, if your addon settings are kept there), so commit them with your site.

The wording defaults are English and go through Laravel's translator, so on a multilingual site you can translate them in `lang/{locale}.json` instead of filling in the Wording tab.

## `config/promo-bar.php`

Publish it to change it:

```bash
php artisan vendor:publish --tag=promo-bar-config
```

| Option | Default | |
| --- | --- | --- |
| `driver` | `file` (`PROMO_BAR_DRIVER`) | Where bars are kept: `file` or `database`. See below. |
| `drivers.file.directory` | `content/promo-bars` | The file driver's folder. |
| `drivers.database.connection` | default connection (`PROMO_BAR_DB_CONNECTION`) | The database driver's connection. |
| `drivers.database.table` | `promo_bars` | Its table. |
| `static_caching.invalidate` | `true` | Clear cached pages when bars change, start or end. See [Static caching](caching.md). |
| `static_caching.schedule` | `true` | Run `promo-bar:tick` every minute on Laravel's scheduler. |
| `audience_cookie.name` | `promo_bar_audience` | The signed-in cookie's name. |
| `audience_cookie.minutes` | 30 days | How long it lasts (it's removed on sign-out). |

## Storage

Bars are kept as YAML files in `content/promo-bars`, one per bar, named by its ID, and indexed by the Stache like entries. They're content, so commit them.

To keep them in the database:

```bash
php artisan vendor:publish --tag=promo-bar-migrations
php artisan migrate
php please promo-bar:transfer --from=file --to=database
```

Then set `PROMO_BAR_DRIVER=database` in `.env`. The transfer copies every bar with its ID, so visitors' dismissals still apply. Run it the other way (`--from=database --to=file`) to go back.
