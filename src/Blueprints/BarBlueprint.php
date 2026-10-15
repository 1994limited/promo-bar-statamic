<?php

namespace NineteenNinetyFour\PromoBar\Blueprints;

use NineteenNinetyFour\PromoBar\Integrations\Cargo;
use NineteenNinetyFour\PromoBar\Settings;
use NineteenNinetyFour\PromoBar\Styles;
use Statamic\Facades\Blueprint;
use Statamic\Facades\Site;
use Statamic\Fields\Blueprint as BlueprintInstance;

/**
 * The fields a bar is edited with in the Control Panel. Cargo's fields
 * (codes from its discounts, free delivery) are only there when Cargo is
 * installed, and Sites only on a multisite.
 */
class BarBlueprint
{
    public function __construct(protected Cargo $cargo, protected Settings $settings) {}

    public function make(): BlueprintInstance
    {
        return Blueprint::make('promo_bar')->setNamespace('promo-bar')->setContents([
            'tabs' => [
                'content' => ['display' => __('Content'), 'sections' => $this->content()],
                'schedule' => ['display' => __('Schedule'), 'sections' => $this->schedule()],
                'where' => ['display' => __('Where'), 'sections' => $this->where()],
                'style' => ['display' => __('Style'), 'sections' => $this->style()],
            ],
        ]);
    }

    protected function content(): array
    {
        $codeOptions = ['none' => __('None'), 'text' => __('Type a code')];

        if ($this->cargo->installed()) {
            $codeOptions['cargo'] = __('From a Cargo discount');
        }

        $sections = [
            [
                'fields' => [
                    $this->field('preview', ['type' => 'promo_bar_preview', 'display' => __('Preview'), 'hide_display' => true, 'mode' => 'bar']),
                    $this->field('title', ['type' => 'text', 'display' => __('Name'), 'instructions' => __('For you: shown in the Control Panel and sent to analytics. Visitors don’t see it.'), 'validate' => ['required', 'max:100'], 'width' => 66]),
                    $this->field('enabled', ['type' => 'toggle', 'display' => __('On'), 'instructions' => __('Off hides it everywhere, whatever its dates.'), 'default' => true, 'width' => 33]),
                    $this->field('message', [
                        'type' => 'bard',
                        'display' => __('Message'),
                        'instructions' => __('One or two short lines. Bold, italic and links.'),
                        'buttons' => ['bold', 'italic', 'anchor'],
                        'inline' => true,
                        'inline_hard_breaks' => true,
                        'smart_typography' => true,
                        'remove_empty_nodes' => 'trim',
                        'validate' => ['required'],
                    ]),
                ],
            ],
            [
                'display' => __('Button'),
                'instructions' => __('A link styled as a button. Leave the label empty for none.'),
                'fields' => [
                    $this->field('cta_label', ['type' => 'text', 'display' => __('Label'), 'validate' => ['nullable', 'max:40', 'required_with:cta_url'], 'width' => 33]),
                    $this->field('cta_url', ['type' => 'link', 'display' => __('Link to'), 'width' => 50]),
                    $this->field('cta_new_tab', ['type' => 'toggle', 'display' => __('New tab'), 'width' => 16]),
                ],
            ],
            [
                'display' => __('Countdown and code'),
                'fields' => [
                    $this->field('countdown', ['type' => 'toggle', 'display' => __('Count down to the end'), 'instructions' => __('Counts down to the end date (set it under Schedule). Without JavaScript it shows the date.'), 'width' => 50]),
                    $this->field('countdown_label', ['type' => 'text', 'display' => __('Countdown label'), 'placeholder' => $this->settings->text('countdown_label'), 'width' => 50, 'if' => ['countdown' => 'equals true']]),
                    $this->field('code_source', ['type' => 'button_group', 'display' => __('Discount code'), 'instructions' => __('Shown with a Copy button.'), 'options' => $codeOptions, 'default' => 'none', 'width' => 50]),
                    $this->field('code', ['type' => 'text', 'display' => __('Code'), 'validate' => ['nullable', 'required_if:code_source,text', 'max:50'], 'width' => 50, 'if' => ['code_source' => 'equals text']]),
                    ...($this->cargo->installed() ? [
                        $this->field('cargo_discount', ['type' => 'select', 'display' => __('Cargo discount'), 'instructions' => __('Shows its code, and only while the discount can be used.'), 'options' => $this->cargo->codeOptions(), 'searchable' => true, 'validate' => ['nullable', 'required_if:code_source,cargo'], 'width' => 50, 'if' => ['code_source' => 'equals cargo']]),
                    ] : []),
                ],
            ],
        ];

        if ($this->cargo->installed()) {
            $sections[] = [
                'display' => __('Free delivery'),
                'instructions' => __('How far the basket is from free delivery. Visitors without a basket, or without JavaScript, see the message above.'),
                'fields' => [
                    $this->field('free_delivery', ['type' => 'toggle', 'display' => __('Show free delivery progress')]),
                    $this->field('free_delivery_source', ['type' => 'button_group', 'display' => __('Free delivery from'), 'options' => ['amount' => __('An amount'), 'discount' => __('A Cargo free shipping discount')], 'default' => 'amount', 'width' => 50, 'if' => ['free_delivery' => 'equals true']]),
                    $this->field('free_delivery_threshold', ['type' => 'money', 'display' => __('Basket total for free delivery'), 'validate' => ['nullable', 'required_if:free_delivery_source,amount'], 'width' => 50, 'if' => ['free_delivery' => 'equals true', 'free_delivery_source' => 'equals amount']]),
                    $this->field('free_delivery_discount', ['type' => 'select', 'display' => __('Free shipping discount'), 'instructions' => __('Automatic free shipping discounts with a minimum order value. The bar ends when the discount does.'), 'options' => $this->cargo->freeShippingOptions(), 'validate' => ['nullable', 'required_if:free_delivery_source,discount'], 'width' => 50, 'if' => ['free_delivery' => 'equals true', 'free_delivery_source' => 'equals discount']]),
                    $this->field('free_delivery_progress', ['type' => 'text', 'display' => __('On the way'), 'instructions' => __(':remaining is the amount still to spend.'), 'placeholder' => $this->settings->text('free_delivery_progress'), 'width' => 50, 'if' => ['free_delivery' => 'equals true']]),
                    $this->field('free_delivery_unlocked', ['type' => 'text', 'display' => __('Reached'), 'placeholder' => $this->settings->text('free_delivery_unlocked'), 'width' => 50, 'if' => ['free_delivery' => 'equals true']]),
                ],
            ];
        }

        $sections[] = [
            'display' => __('Dismissing'),
            'fields' => [
                $this->field('dismissible', ['type' => 'toggle', 'display' => __('Visitors can dismiss it'), 'instructions' => __('Remembered in their browser. Changing the message shows it again.'), 'default' => true, 'width' => 50]),
                $this->field('dismiss_days', ['type' => 'integer', 'display' => __('Show again after (days)'), 'instructions' => __('Leave empty to keep it hidden.'), 'placeholder' => $this->settings->get('dismiss_days'), 'validate' => ['nullable', 'integer', 'min:1', 'max:365'], 'width' => 50, 'if' => ['dismissible' => 'equals true']]),
            ],
        ];

        return $sections;
    }

    protected function schedule(): array
    {
        return [[
            'instructions' => __('Leave both empty to show it until you switch it off. Times are :timezone.', ['timezone' => config('app.timezone')]),
            'fields' => [
                $this->field('starts_at', ['type' => 'date', 'display' => __('Starts'), 'time_enabled' => true, 'width' => 50]),
                $this->field('ends_at', ['type' => 'date', 'display' => __('Ends'), 'time_enabled' => true, 'width' => 50]),
            ],
        ]];
    }

    protected function where(): array
    {
        $pages = [
            ...(Site::multiEnabled() ? [
                $this->field('sites', ['type' => 'sites', 'display' => __('Sites'), 'instructions' => __('Leave empty for every site.'), 'mode' => 'select']),
            ] : []),
            $this->field('pages', ['type' => 'button_group', 'display' => __('Pages'), 'options' => ['everywhere' => __('Everywhere'), 'only' => __('Only on…'), 'except' => __('Everywhere except…')], 'default' => 'everywhere']),
            $this->field('entries', ['type' => 'entries', 'display' => __('Entries'), 'mode' => 'stack', 'create' => false, 'width' => 50, 'unless' => ['pages' => 'equals everywhere']]),
            $this->field('collections', ['type' => 'collections', 'display' => __('Collections'), 'instructions' => __('Every entry in them.'), 'mode' => 'select', 'width' => 50, 'unless' => ['pages' => 'equals everywhere']]),
            $this->field('urls', ['type' => 'textarea', 'display' => __('URLs'), 'instructions' => __('One per line, as in the address bar. Use * for “anything”: /shop/* or */sale.'), 'placeholder' => "/shop/*\n/checkout*", 'unless' => ['pages' => 'equals everywhere']]),
        ];

        return [
            ['display' => __('Pages'), 'fields' => $pages],
            [
                'display' => __('Visitors'),
                'instructions' => __('Chosen in the browser, so it works on cached pages. It decides what’s shown, not who may see it: don’t put anything private in a bar.'),
                'fields' => [
                    $this->field('audience', ['type' => 'button_group', 'display' => __('Show to'), 'options' => ['everyone' => __('Everyone'), 'guests' => __('Guests only'), 'signed_in' => __('Signed-in only')], 'default' => 'everyone']),
                    $this->field('groups', ['type' => 'user_groups', 'display' => __('User groups'), 'instructions' => __('Only people in one of these groups. Leave empty for anyone signed in.'), 'mode' => 'select', 'if' => ['audience' => 'equals signed_in']]),
                ],
            ],
        ];
    }

    protected function style(): array
    {
        $default = Styles::labels()[$this->settings->get('default_style')] ?? Styles::labels()['solid'];
        $options = ['' => __('Default (:style)', ['style' => $default])] + Styles::labels();

        $colour = fn (string $handle, string $display, array $extra = []) => $this->field('colour_'.$handle, [
            'type' => 'color',
            'display' => $display,
            'swatches' => [],
            'width' => 25,
            'if' => ['custom_colours' => 'equals true', ...($extra['if'] ?? [])],
        ]);

        return [[
            'fields' => [
                $this->field('style_preview', ['type' => 'promo_bar_preview', 'display' => __('Preview'), 'hide_display' => true, 'mode' => 'bar']),
                $this->field('style', ['type' => 'select', 'display' => __('Style'), 'instructions' => __('The presets are edited under Tools → Addons → Promo Bar → Settings.'), 'options' => $options, 'default' => '', 'width' => 50]),
                $this->field('custom_colours', ['type' => 'toggle', 'display' => __('Own colours'), 'instructions' => __('Change this bar’s colours only.'), 'width' => 50, 'unless' => ['style' => 'equals none']]),
                $colour('background', __('Background')),
                $colour('background_2', __('Second background'), ['if' => ['style' => 'equals gradient']]),
                $colour('text', __('Text')),
                $colour('accent', __('Button')),
                $colour('accent_text', __('Button text')),
            ],
        ]];
    }

    protected function field(string $handle, array $config): array
    {
        return ['handle' => $handle, 'field' => $config];
    }
}
