<?php

namespace NineteenNinetyFour\PromoBar\Fieldtypes;

use NineteenNinetyFour\PromoBar\Renderer;
use NineteenNinetyFour\PromoBar\Settings;
use NineteenNinetyFour\PromoBar\Styles;
use Statamic\Fields\Fieldtype;

/**
 * A live preview in the Control Panel: of the bar being edited, or of
 * every preset on the settings page. It stores nothing.
 */
class PromoBarPreview extends Fieldtype
{
    protected static $handle = 'promo_bar_preview';

    protected $selectable = false;

    protected $localizable = false;

    public function preload(): array
    {
        $settings = app(Settings::class);
        $styles = app(Styles::class);

        return [
            'mode' => $this->config('mode', 'bar'),
            'css' => Styles::baseCss(),
            'phone_css' => Styles::phoneCss(),
            'phone_width' => $settings->phoneWidth(),
            'presets' => collect(array_keys(Styles::presets()))->mapWithKeys(fn ($handle) => [$handle => $styles->preset($handle)])->all(),
            'defaults' => Styles::presets(),
            'labels' => Styles::labels(),
            'default_style' => $settings->get('default_style'),
            'words' => app(Renderer::class)->labels() + [
                'countdown' => $settings->text('countdown_label'),
                'code' => $settings->text('code_label'),
                'progress' => $settings->text('free_delivery_progress'),
            ],
        ];
    }

    public function process($data)
    {
        return null;
    }

    public function preProcess($data)
    {
        return null;
    }
}
