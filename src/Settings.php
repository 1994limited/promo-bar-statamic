<?php

namespace NineteenNinetyFour\PromoBar;

use Statamic\Contracts\Addons\SettingsRepository;
use Statamic\Facades\Addon;
use Throwable;

/**
 * The settings edited in the Control Panel (Tools → Addons → Promo Bar →
 * Settings): wording, how several bars show, the style presets and
 * analytics. Read as saved, with the defaults below filling any gaps, so
 * nothing needs saving before the addon works.
 */
class Settings
{
    public const PACKAGE = '1994/promo-bar-statamic';

    protected ?array $saved = null;

    /**
     * The defaults, which the settings page also starts with.
     */
    public static function defaults(): array
    {
        return [
            'display' => 'first',
            'rotate_interval' => 6,
            'sticky' => false,
            'default_style' => 'solid',
            'dismiss_days' => null,
            'phone_width' => 640,
            'phone_first_only' => true,
            'region_label' => __('Announcements'),
            'dismiss_label' => __('Dismiss'),
            'countdown_label' => __('Ends in'),
            'ends_label' => __('Ends'),
            'code_label' => __('Use code'),
            'copy_label' => __('Copy'),
            'copied_label' => __('Copied'),
            'previous_label' => __('Previous'),
            'next_label' => __('Next'),
            'pause_label' => __('Pause'),
            'play_label' => __('Play'),
            'free_delivery_progress' => __(':remaining away from free delivery'),
            'free_delivery_unlocked' => __('You’ve unlocked free delivery'),
            'analytics_providers' => [],
            'analytics_events' => ['click', 'dismiss', 'copy'],
            'analytics_event_view' => 'promo_bar_view',
            'analytics_event_click' => 'promo_bar_click',
            'analytics_event_dismiss' => 'promo_bar_dismiss',
            'analytics_event_copy' => 'promo_bar_copy',
            'analytics_custom_function' => null,
            'analytics_custom_arguments' => 'object',
            'analytics_custom_properties' => [],
            'analytics_custom_endpoint' => null,
            'analytics_debug' => false,
        ];
    }

    /**
     * Every setting as saved, without defaults.
     */
    public function saved(): array
    {
        if ($this->saved !== null) {
            return $this->saved;
        }

        try {
            $addon = Addon::get(self::PACKAGE);
            $saved = $addon ? app(SettingsRepository::class)->find($addon->id())?->raw() : null;
        } catch (Throwable) {
            $saved = null;
        }

        return $this->saved = is_array($saved) ? $saved : [];
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $value = $this->saved()[$key] ?? null;

        if ($value === null || $value === '' || (is_string($value) && trim($value) === '')) {
            return $default ?? static::defaults()[$key] ?? null;
        }

        return is_string($value) ? trim($value) : $value;
    }

    /**
     * The widest screen, in CSS pixels, counted as a phone.
     */
    public function phoneWidth(): int
    {
        $width = $this->get('phone_width');

        return is_numeric($width) ? max(320, min(1200, (int) $width)) : 640;
    }

    public function text(string $key): string
    {
        return (string) $this->get($key);
    }

    public function bool(string $key): bool
    {
        $value = $this->get($key);

        return is_bool($value) ? $value : (bool) filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Forget what was read, as after the settings are saved.
     */
    public function refresh(): static
    {
        $this->saved = null;

        return $this;
    }
}
