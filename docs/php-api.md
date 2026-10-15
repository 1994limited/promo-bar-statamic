# PHP API

## The `PromoBars` service

```php
use NineteenNinetyFour\PromoBar\Bar;
use NineteenNinetyFour\PromoBar\Page;
use NineteenNinetyFour\PromoBar\PromoBars;

$bars = app(PromoBars::class);

$bars->all();                 // every bar, highest first
$bars->find($id);             // one bar, or null
$bars->live(Page::current()); // live on the current page, before the visitor is considered
$bars->liveAnywhere();        // live on any page
$bars->isLive($bar);          // on, within its dates, and its Cargo discount still usable

$bar = Bar::make([
    'title' => 'Spring launch',
    'message' => [['type' => 'text', 'text' => 'New spring blend out now']],
    'cta_label' => 'Have a look',
    'cta_url' => '/shop',
    'ends_at' => '2026-04-30 23:59',
]);

$bars->save($bar);            // new bars go to the bottom
$bars->reorder([$id1, $id2]); // highest first; bars not listed keep their place after
$bars->delete($bar);
```

A bar's fields are the Control Panel form's (`$bar->get('cta_label')`, `$bar->set('enabled', false)`), with `message` as Bard's value and dates as `Y-m-d H:i` in the app's timezone. `$bar->status()` is a `BarStatus`: `Live`, `Scheduled`, `Expired` or `Disabled`.

## Events

| Event | |
| --- | --- |
| `NineteenNinetyFour\PromoBar\Events\BarSaved` | `$event->bar`, and `$event->original` (as it was, or null when new). |
| `NineteenNinetyFour\PromoBar\Events\BarDeleted` | `$event->bar`. |
| `NineteenNinetyFour\PromoBar\Events\BarsReordered` | `$event->ids`, highest first. |

## Drawing bars yourself

```php
use NineteenNinetyFour\PromoBar\Page;
use NineteenNinetyFour\PromoBar\Renderer;

echo app(Renderer::class)->render(Page::current(), ['mode' => 'stack']);
```

## Your own storage

Implement `NineteenNinetyFour\PromoBar\Contracts\BarRepository` (`all()` in order, `find()`, `save()`, `delete()`) and bind it in a service provider:

```php
$this->app->singleton(\NineteenNinetyFour\PromoBar\Contracts\BarRepository::class, MyBarRepository::class);
```
