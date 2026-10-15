<?php

namespace NineteenNinetyFour\PromoBar;

/**
 * The style presets: six looks a bar can take, each with colours and
 * sizes editable in the settings, and "none" for sites that bring their
 * own CSS. They're written as CSS custom properties, so a site's own
 * stylesheet can change any of them.
 */
class Styles
{
    public const NONE = 'none';

    public const COLOURS = ['background', 'background_2', 'text', 'accent', 'accent_text'];

    public const BUTTON_STYLES = ['filled', 'outline', 'link'];

    public const SIZES = ['small' => '0.8125rem', 'normal' => '0.9375rem', 'large' => '1.0625rem'];

    public const PADDING = ['compact' => '0.375rem', 'normal' => '0.625rem', 'roomy' => '1rem'];

    public const ALIGN = ['start', 'center'];

    public function __construct(protected Settings $settings) {}

    /**
     * Each preset's starting values.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function presets(): array
    {
        return [
            'solid' => ['background' => '#1d4ed8', 'text' => '#ffffff', 'accent' => '#ffffff', 'accent_text' => '#1d4ed8', 'button_style' => 'filled', 'size' => 'normal', 'padding' => 'normal', 'align' => 'center', 'radius' => 4],
            'subtle' => ['background' => '#eff6ff', 'text' => '#1e3a8a', 'accent' => '#1d4ed8', 'accent_text' => '#ffffff', 'button_style' => 'link', 'size' => 'normal', 'padding' => 'normal', 'align' => 'center', 'radius' => 4],
            'dark' => ['background' => '#111827', 'text' => '#f9fafb', 'accent' => '#facc15', 'accent_text' => '#111827', 'button_style' => 'filled', 'size' => 'normal', 'padding' => 'normal', 'align' => 'center', 'radius' => 4],
            'outline' => ['background' => '#ffffff', 'text' => '#111827', 'accent' => '#1d4ed8', 'accent_text' => '#1d4ed8', 'button_style' => 'outline', 'size' => 'normal', 'padding' => 'normal', 'align' => 'center', 'radius' => 4],
            'gradient' => ['background' => '#6d28d9', 'background_2' => '#be185d', 'text' => '#ffffff', 'accent' => '#ffffff', 'accent_text' => '#6d28d9', 'button_style' => 'filled', 'size' => 'normal', 'padding' => 'roomy', 'align' => 'center', 'radius' => 999],
            'minimal' => ['background' => '#f9fafb', 'text' => '#374151', 'accent' => '#374151', 'accent_text' => '#ffffff', 'button_style' => 'link', 'size' => 'small', 'padding' => 'compact', 'align' => 'center', 'radius' => 0],
        ];
    }

    /**
     * @return array<string, string> handle => label
     */
    public static function labels(): array
    {
        return [
            'solid' => __('Solid'),
            'subtle' => __('Subtle'),
            'dark' => __('Dark'),
            'outline' => __('Outline'),
            'gradient' => __('Gradient'),
            'minimal' => __('Minimal'),
            self::NONE => __('None (unstyled)'),
        ];
    }

    /**
     * A preset's values: as edited in the settings, over its defaults, each
     * checked so nothing can break out of the CSS.
     */
    public function preset(string $handle): array
    {
        $defaults = static::presets()[$handle] ?? static::presets()['solid'];
        $saved = $this->settings->get('style_'.$handle);
        $saved = is_array($saved) ? $saved : [];

        return static::clean($saved, $defaults);
    }

    /**
     * Values checked against what each one may be, falling back to the
     * given defaults.
     */
    public static function clean(array $values, array $defaults = []): array
    {
        $clean = [];

        foreach (self::COLOURS as $key) {
            $colour = static::colour($values[$key] ?? null) ?? static::colour($defaults[$key] ?? null);

            if ($colour !== null) {
                $clean[$key] = $colour;
            }
        }

        $clean['button_style'] = in_array($values['button_style'] ?? null, self::BUTTON_STYLES, true) ? $values['button_style'] : ($defaults['button_style'] ?? 'filled');
        $clean['size'] = array_key_exists($values['size'] ?? '', self::SIZES) ? $values['size'] : ($defaults['size'] ?? 'normal');
        $clean['padding'] = array_key_exists($values['padding'] ?? '', self::PADDING) ? $values['padding'] : ($defaults['padding'] ?? 'normal');
        $clean['align'] = in_array($values['align'] ?? null, self::ALIGN, true) ? $values['align'] : ($defaults['align'] ?? 'center');
        $clean['radius'] = is_numeric($values['radius'] ?? null) ? max(0, min(999, (int) $values['radius'])) : (int) ($defaults['radius'] ?? 0);

        return $clean;
    }

    /**
     * The preset a bar uses: its own, or the site default.
     */
    public function for(Bar $bar): string
    {
        $style = $bar->get('style');

        if (! is_string($style) || ! array_key_exists($style, static::labels())) {
            $style = $this->settings->get('default_style');
        }

        return is_string($style) && array_key_exists($style, static::labels()) ? $style : 'solid';
    }

    /**
     * A CSS hex colour, or null if it isn't one.
     */
    public static function colour(mixed $colour): ?string
    {
        if (! is_string($colour)) {
            return null;
        }

        $colour = trim($colour);

        return preg_match('/^#(?:[0-9a-fA-F]{3}){1,2}$/', $colour) ? strtolower($colour) : null;
    }

    /**
     * The WCAG contrast ratio between two colours (1 to 21).
     */
    public static function contrast(string $a, string $b): float
    {
        $la = static::luminance($a);
        $lb = static::luminance($b);

        return round((max($la, $lb) + 0.05) / (min($la, $lb) + 0.05), 2);
    }

    public static function luminance(string $colour): float
    {
        $hex = ltrim(static::colour($colour) ?? '#000000', '#');

        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }

        [$r, $g, $b] = array_map(function (string $pair) {
            $c = hexdec($pair) / 255;

            return $c <= 0.04045 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
        }, str_split($hex, 2));

        return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
    }

    /**
     * The custom properties for a set of values.
     */
    public static function properties(array $values): string
    {
        $map = [
            'background' => '--promo-bar-background',
            'background_2' => '--promo-bar-background-2',
            'text' => '--promo-bar-text',
            'accent' => '--promo-bar-accent',
            'accent_text' => '--promo-bar-accent-text',
        ];

        $css = [];

        foreach ($map as $key => $property) {
            if (isset($values[$key]) && static::colour($values[$key])) {
                $css[] = $property.':'.$values[$key];
            }
        }

        if (isset($values['size'])) {
            $css[] = '--promo-bar-font-size:'.self::SIZES[$values['size']];
        }

        if (isset($values['padding'])) {
            $css[] = '--promo-bar-padding:'.self::PADDING[$values['padding']];
        }

        if (isset($values['align'])) {
            $css[] = '--promo-bar-align:'.($values['align'] === 'start' ? 'flex-start' : 'center');
            $css[] = '--promo-bar-text-align:'.($values['align'] === 'start' ? 'start' : 'center');
        }

        if (isset($values['radius'])) {
            $css[] = '--promo-bar-radius:'.(int) $values['radius'].'px';
        }

        return implode(';', $css);
    }

    /**
     * The stylesheet: the base rules, then each preset's values from the
     * settings, then any bar's own colours. The presets' values are wrapped
     * in :where(), with no specificity, so any rule in a site's own CSS
     * changes them whatever order the stylesheets load in.
     *
     * @param  iterable<Bar>  $bars
     */
    public function css(iterable $bars = []): string
    {
        $css = static::baseCss();
        $css .= "\n@media (max-width: {$this->settings->phoneWidth()}px) {\n".static::phoneCss()."}\n";

        foreach (array_keys(static::presets()) as $handle) {
            $css .= "\n:where(.promo-bar--{$handle}){".static::properties($this->preset($handle)).'}';
        }

        foreach ($bars as $bar) {
            if ($overrides = $this->overrides($bar)) {
                $css .= "\n.promo-bar__bar[data-promo-bar-id=\"{$bar->id()}\"]{".static::properties($overrides).'}';
            }
        }

        return $css."\n";
    }

    /**
     * A bar's own colours, where it has any.
     */
    public function overrides(Bar $bar): array
    {
        if (! $bar->get('custom_colours')) {
            return [];
        }

        $colours = [];

        foreach (self::COLOURS as $key) {
            if ($colour = static::colour($bar->get('colour_'.$key))) {
                $colours[$key] = $colour;
            }
        }

        return $colours;
    }

    /**
     * The rules for phones, without their media query: the Control Panel's
     * preview applies them to its phone view instead.
     */
    public static function phoneCss(): string
    {
        return (string) file_get_contents(__DIR__.'/../resources/frontend/promo-bar-phone.css');
    }

    public static function baseCss(): string
    {
        return (string) file_get_contents(__DIR__.'/../resources/frontend/promo-bar.css');
    }

    /**
     * A short fingerprint of the stylesheet, for its URL.
     *
     * @param  iterable<Bar>  $bars
     */
    public function version(iterable $bars = []): string
    {
        return substr(md5($this->css($bars)), 0, 12);
    }
}
