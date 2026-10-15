<?php

namespace NineteenNinetyFour\PromoBar\Blueprints;

use NineteenNinetyFour\PromoBar\Analytics;
use NineteenNinetyFour\PromoBar\Settings;
use NineteenNinetyFour\PromoBar\Styles;

/**
 * The settings page (Tools → Addons → Promo Bar → Settings): how bars
 * show, the style presets, wording and analytics.
 */
class SettingsBlueprint
{
    public function contents(): array
    {
        $defaults = Settings::defaults();

        return [
            'tabs' => [
                'general' => ['display' => __('General'), 'sections' => $this->general($defaults)],
                'styles' => ['display' => __('Styles'), 'sections' => $this->styles()],
                'wording' => ['display' => __('Wording'), 'sections' => $this->wording($defaults)],
                'analytics' => ['display' => __('Analytics'), 'sections' => $this->analytics($defaults)],
            ],
        ];
    }

    protected function general(array $defaults): array
    {
        return [[
            'display' => __('When several bars are live'),
            'instructions' => __('Bars are in the order on the Promo Bars page: drag to change it. A template can choose otherwise with mode="…".'),
            'fields' => [
                $this->field('display', ['type' => 'button_group', 'display' => __('Show'), 'options' => ['first' => __('The first only'), 'stack' => __('All, stacked'), 'rotate' => __('One at a time, rotating')], 'default' => $defaults['display']]),
                $this->field('rotate_interval', ['type' => 'integer', 'display' => __('Seconds each'), 'instructions' => __('Rotating pauses on hover and focus, has a pause button, and doesn’t move by itself for visitors who prefer reduced motion.'), 'default' => $defaults['rotate_interval'], 'validate' => ['nullable', 'integer', 'min:2', 'max:60'], 'width' => 50, 'if' => ['display' => 'equals rotate']]),
            ],
        ], [
            'display' => __('Bars'),
            'fields' => [
                $this->field('default_style', ['type' => 'select', 'display' => __('Default style'), 'instructions' => __('For bars that don’t choose one.'), 'options' => Styles::labels(), 'default' => $defaults['default_style'], 'width' => 50]),
                $this->field('sticky', ['type' => 'toggle', 'display' => __('Stick to the top on scroll'), 'default' => false, 'width' => 50]),
                $this->field('dismiss_days', ['type' => 'integer', 'display' => __('Dismissed bars come back after (days)'), 'instructions' => __('Leave empty to keep them hidden. Each bar can choose its own.'), 'validate' => ['nullable', 'integer', 'min:1', 'max:365'], 'width' => 50]),
            ],
        ]];
    }

    protected function styles(): array
    {
        $sections = [[
            'instructions' => __('Each preset’s colours and sizes. Every bar using a preset changes with it; a bar can also have its own colours.'),
            'fields' => [
                $this->field('styles_preview', ['type' => 'promo_bar_preview', 'display' => __('Preview'), 'hide_display' => true, 'full_width_setting' => true, 'mode' => 'settings']),
            ],
        ]];

        foreach (Styles::presets() as $handle => $preset) {
            $sections[] = [
                'display' => Styles::labels()[$handle],
                'collapsible' => true,
                'collapsed' => $handle !== 'solid',
                'fields' => [
                    $this->field('style_'.$handle, [
                        'type' => 'group',
                        'display' => Styles::labels()[$handle],
                        'hide_display' => true,
                        'full_width_setting' => true,
                        'fullscreen' => false,
                        'border' => false,
                        'fields' => $this->presetFields($handle, $preset),
                    ]),
                ],
            ];
        }

        return $sections;
    }

    protected function presetFields(string $handle, array $preset): array
    {
        $colour = fn (string $key, string $display) => $this->field($key, ['type' => 'color', 'display' => $display, 'default' => $preset[$key], 'swatches' => [], 'width' => 25]);

        return array_values(array_filter([
            $colour('background', $handle === 'gradient' ? __('Background from') : __('Background')),
            $handle === 'gradient' ? $colour('background_2', __('Background to')) : null,
            $colour('text', __('Text')),
            $colour('accent', $handle === 'outline' ? __('Line and button') : __('Button')),
            $colour('accent_text', __('Button text')),
            $this->field('button_style', ['type' => 'button_group', 'display' => __('Button'), 'options' => ['filled' => __('Filled'), 'outline' => __('Outline'), 'link' => __('Link')], 'default' => $preset['button_style'], 'width' => 50]),
            $this->field('radius', ['type' => 'integer', 'display' => __('Button corners (px)'), 'default' => $preset['radius'], 'validate' => ['nullable', 'integer', 'min:0', 'max:999'], 'width' => 50]),
            $this->field('size', ['type' => 'button_group', 'display' => __('Text size'), 'options' => ['small' => __('Small'), 'normal' => __('Normal'), 'large' => __('Large')], 'default' => $preset['size'], 'width' => 33]),
            $this->field('padding', ['type' => 'button_group', 'display' => __('Height'), 'options' => ['compact' => __('Compact'), 'normal' => __('Normal'), 'roomy' => __('Roomy')], 'default' => $preset['padding'], 'width' => 33]),
            $this->field('align', ['type' => 'button_group', 'display' => __('Align'), 'options' => ['start' => __('Left'), 'center' => __('Centre')], 'default' => $preset['align'], 'width' => 33]),
        ]));
    }

    protected function wording(array $defaults): array
    {
        $text = fn (string $key, string $display, array $extra = []) => $this->field($key, ['type' => 'text', 'display' => $display, 'placeholder' => $defaults[$key], 'width' => 50, ...$extra]);

        return [[
            'instructions' => __('Leave any empty for the wording shown. Bars can change the countdown and free delivery wording for themselves.'),
            'fields' => [
                $text('region_label', __('Bars’ name for screen readers')),
                $text('dismiss_label', __('Dismiss button')),
                $text('countdown_label', __('Countdown')),
                $text('ends_label', __('End date (without JavaScript)')),
                $text('code_label', __('Before a code')),
                $text('copy_label', __('Copy button')),
                $text('copied_label', __('Once copied')),
                $text('previous_label', __('Previous (rotating)')),
                $text('next_label', __('Next (rotating)')),
                $text('pause_label', __('Pause (rotating)')),
                $text('play_label', __('Play (rotating)')),
                $text('free_delivery_progress', __('Free delivery: on the way'), ['instructions' => __(':remaining is the amount still to spend.')]),
                $text('free_delivery_unlocked', __('Free delivery: reached')),
            ],
        ]];
    }

    protected function analytics(array $defaults): array
    {
        $events = ['view' => __('Views'), 'click' => __('Button clicks'), 'dismiss' => __('Dismissals'), 'copy' => __('Code copies')];

        return [[
            'instructions' => __('Events go to the trackers already on your pages, through their own scripts, so your consent settings still apply. Nothing is sent from your server and no cookies are set.'),
            'fields' => [
                $this->field('analytics_providers', ['type' => 'checkboxes', 'display' => __('Send to'), 'options' => Analytics::providerLabels(), 'inline' => false]),
                $this->field('analytics_events', ['type' => 'checkboxes', 'display' => __('Events'), 'instructions' => __('Views are counted once a visit for each bar, but still count against Plausible’s and Fathom’s event allowances.'), 'options' => $events, 'default' => $defaults['analytics_events'], 'inline' => true]),
                ...collect(array_keys($events))->map(fn ($event) => $this->field('analytics_event_'.$event, ['type' => 'text', 'display' => __('Event name: :event', ['event' => $events[$event]]), 'placeholder' => $defaults['analytics_event_'.$event], 'validate' => ['nullable', 'regex:/^[\pL\pN_ .:\-]{1,64}$/u'], 'width' => 25]))->all(),
                $this->field('analytics_debug', ['type' => 'toggle', 'display' => __('Log events to the browser console'), 'instructions' => __('To check it’s set up. Turn off when done.')]),
            ],
        ], [
            'display' => __('Custom'),
            'instructions' => __('For any other tracker: name a function already on your pages, and/or a URL to send each event to.'),
            'fields' => [
                $this->field('analytics_custom_function', ['type' => 'text', 'display' => __('Function'), 'instructions' => __('Its name on window, such as myTracker.track or _paq.push. Only a name: no code.'), 'validate' => ['nullable', 'regex:/^[A-Za-z_$][A-Za-z0-9_$]*(\.[A-Za-z_$][A-Za-z0-9_$]*){0,4}$/'], 'width' => 50, 'if' => ['analytics_providers' => 'contains custom']]),
                $this->field('analytics_custom_arguments', ['type' => 'select', 'display' => __('Called with'), 'options' => [
                    'object' => __('(event, { id, name, action, url, site, page })'),
                    'values' => __('(event, bar name, button URL)'),
                    'array' => __('([“trackEvent”, “Promo Bar”, event, bar name]), as Matomo’s _paq.push'),
                ], 'default' => $defaults['analytics_custom_arguments'], 'width' => 50, 'if' => ['analytics_providers' => 'contains custom']]),
                $this->field('analytics_custom_properties', ['type' => 'array', 'display' => __('Rename properties'), 'instructions' => __('Send a property under another name, such as id as campaign_id. Leave a name empty to keep it.'), 'mode' => 'keyed', 'keys' => array_combine(Analytics::PROPERTIES, Analytics::PROPERTIES), 'if' => ['analytics_providers' => 'contains custom']]),
                $this->field('analytics_custom_endpoint', ['type' => 'text', 'display' => __('Also send to a URL'), 'instructions' => __('Each event is POSTed there as JSON, with the browser’s sendBeacon. For your own endpoint or a server-side tag.'), 'input_type' => 'url', 'validate' => ['nullable', 'max:500'], 'if' => ['analytics_providers' => 'contains custom']]),
            ],
        ]];
    }

    protected function field(string $handle, array $config): array
    {
        return ['handle' => $handle, 'field' => $config];
    }
}
