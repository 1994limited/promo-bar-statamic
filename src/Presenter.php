<?php

namespace NineteenNinetyFour\PromoBar;

use Carbon\CarbonImmutable;
use NineteenNinetyFour\PromoBar\Blueprints\BarBlueprint;
use NineteenNinetyFour\PromoBar\Integrations\Cargo;
use Statamic\Facades\Site;
use Statamic\Fields\Blueprint;
use Statamic\Fields\Value;
use Throwable;

/**
 * What a bar's template is given: its message as HTML, its button, code,
 * countdown and free delivery progress, and the attributes the script reads.
 */
class Presenter
{
    protected ?Blueprint $blueprint = null;

    public function __construct(
        protected Settings $settings,
        protected Styles $styles,
        protected Cargo $cargo,
    ) {}

    /**
     * @param  Visitor|null  $visitor  Who's looking, when the page is drawn for them alone (not
     *                                 cached): free delivery progress is then filled in here.
     */
    public function bar(Bar $bar, ?Visitor $visitor = null, ?CarbonImmutable $now = null): array
    {
        $now ??= CarbonImmutable::now();
        $style = $this->styles->for($bar);
        $buttonStyle = $style === Styles::NONE ? null : $this->styles->preset($style)['button_style'];
        $ends = $bar->endsAt();
        $dismissible = (bool) $bar->get('dismissible', false);

        $classes = array_filter([
            'promo-bar__bar',
            'promo-bar--'.$style,
            $style !== Styles::NONE ? 'promo-bar--themed' : null,
            $buttonStyle ? 'promo-bar--button-'.$buttonStyle : null,
            $dismissible ? 'promo-bar--dismissible' : null,
        ]);

        $freeDelivery = $this->freeDelivery($bar, $visitor, $now);
        $message = $this->message($bar);

        if ($freeDelivery) {
            $message = str_replace(':threshold', e($freeDelivery['threshold_formatted']), $message);
        }

        return [
            'id' => $bar->id(),
            'name' => $bar->title(),
            'key' => $bar->id().':'.$bar->contentHash(),
            'style' => $style,
            'classes' => implode(' ', $classes),
            'message' => $message,
            'cta' => $this->cta($bar),
            'countdown' => $bar->get('countdown') && $ends ? [
                'label' => (string) $bar->get('countdown_label', $this->settings->text('countdown_label')),
                'ends_label' => $this->settings->text('ends_label'),
                'ends_iso' => $ends->toIso8601String(),
                'ends_text' => $this->formatDate($ends),
            ] : null,
            'code' => $this->code($bar, $now),
            'free_delivery' => $freeDelivery,
            'ends_iso' => $ends?->toIso8601String(),
            'audience' => in_array($bar->get('audience'), ['guests', 'signed_in'], true) ? $bar->get('audience') : null,
            'groups' => implode(',', array_filter((array) $bar->get('groups', []), 'is_string')),
            'dismissible' => $dismissible,
            'dismiss_days' => $dismissible ? $this->dismissDays($bar) : null,
            'dismiss_label' => __(':action: :bar', ['action' => $this->settings->text('dismiss_label'), 'bar' => $bar->title()]),
        ];
    }

    public function message(Bar $bar): string
    {
        $value = $bar->get('message');

        if (is_string($value)) {
            return e($value);
        }

        try {
            return trim((string) $this->field('message', $value));
        } catch (Throwable $e) {
            report($e);

            return '';
        }
    }

    public function cta(Bar $bar): ?array
    {
        $label = trim((string) $bar->get('cta_label', ''));
        $url = $bar->get('cta_url');

        if ($label === '' || blank($url)) {
            return null;
        }

        try {
            $resolved = $this->field('cta_url', $url);
            $resolved = is_object($resolved) && method_exists($resolved, 'url') ? $resolved->url() : (string) $resolved;
        } catch (Throwable) {
            $resolved = is_string($url) ? $url : null;
        }

        // Only links a browser follows safely: no javascript: or data: URLs.
        if (! is_string($resolved) || $resolved === '' || ! preg_match('#^(https?://|/|\#|\?|mailto:|tel:)#i', $resolved)) {
            return null;
        }

        return ['label' => $label, 'url' => $resolved, 'new_tab' => (bool) $bar->get('cta_new_tab')];
    }

    public function code(Bar $bar, ?CarbonImmutable $now = null): ?array
    {
        $code = match ($bar->get('code_source')) {
            'text' => trim((string) $bar->get('code', '')),
            'cargo' => $this->cargo->code($bar, $now),
            default => null,
        };

        return $code ? [
            'code' => $code,
            'label' => $this->settings->text('code_label'),
            'copy_label' => $this->settings->text('copy_label'),
            'copied_label' => $this->settings->text('copied_label'),
        ] : null;
    }

    public function freeDelivery(Bar $bar, ?Visitor $visitor = null, ?CarbonImmutable $now = null): ?array
    {
        if (! $bar->get('free_delivery') || ($threshold = $this->cargo->threshold($bar, $now)) === null) {
            return null;
        }

        $data = [
            'threshold' => $threshold,
            'threshold_formatted' => $this->cargo->money($threshold),
            'progress_text' => (string) $bar->get('free_delivery_progress', $this->settings->text('free_delivery_progress')),
            'unlocked_text' => (string) $bar->get('free_delivery_unlocked', $this->settings->text('free_delivery_unlocked')),
            'state' => null,
        ];

        if ($visitor) {
            $data['state'] = $this->freeDeliveryState($bar, $this->cargo->subtotal(), $now);
        }

        return $data;
    }

    /**
     * How far a basket is from a bar's free delivery: null without a basket.
     *
     * @return array{remaining: int, remaining_formatted: string, percent: int, unlocked: bool, text: string}|null
     */
    public function freeDeliveryState(Bar $bar, ?int $subtotal, ?CarbonImmutable $now = null): ?array
    {
        $threshold = $this->cargo->threshold($bar, $now);

        if ($threshold === null || $subtotal === null || $subtotal <= 0) {
            return null;
        }

        $remaining = max(0, $threshold - $subtotal);
        $unlocked = $remaining === 0;
        $formatted = $this->cargo->money($remaining);

        return [
            'remaining' => $remaining,
            'remaining_formatted' => $formatted,
            'percent' => (int) min(100, floor($subtotal / $threshold * 100)),
            'unlocked' => $unlocked,
            'text' => $unlocked
                ? (string) $bar->get('free_delivery_unlocked', $this->settings->text('free_delivery_unlocked'))
                : str_replace(':remaining', $formatted, (string) $bar->get('free_delivery_progress', $this->settings->text('free_delivery_progress'))),
        ];
    }

    protected function dismissDays(Bar $bar): ?int
    {
        $days = $bar->get('dismiss_days', $this->settings->get('dismiss_days'));

        return is_numeric($days) && (int) $days > 0 ? (int) $days : null;
    }

    public function formatDate(CarbonImmutable $date): string
    {
        $locale = Site::current()?->shortLocale() ?? 'en';

        return $date->setTimezone(config('app.timezone'))->locale($locale)->isoFormat('ddd D MMM, HH:mm');
    }

    /**
     * A field's value as its fieldtype gives it to templates.
     */
    protected function field(string $handle, mixed $value): mixed
    {
        $this->blueprint ??= app(BarBlueprint::class)->make();

        $augmented = $this->blueprint->field($handle)->setValue($value)->augment()->value();

        return $augmented instanceof Value ? $augmented->value() : $augmented;
    }
}
