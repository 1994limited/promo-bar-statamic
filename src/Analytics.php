<?php

namespace NineteenNinetyFour\PromoBar;

/**
 * Sending bar views, clicks, dismissals and code copies to the site's own
 * analytics. Nothing is sent from the server and nothing is stored: the
 * script calls the trackers already on the page (Google Tag Manager, GA4,
 * Plausible, Fathom, Umami, Matomo, or a function of your own).
 */
class Analytics
{
    public const PROVIDERS = ['gtm', 'ga4', 'plausible', 'fathom', 'umami', 'matomo', 'custom'];

    public const EVENTS = ['view', 'click', 'dismiss', 'copy'];

    public const ARGUMENTS = ['object', 'values', 'array'];

    public const PROPERTIES = ['id', 'name', 'action', 'url', 'site', 'page'];

    public function __construct(protected Settings $settings) {}

    /**
     * @return array<string, string>
     */
    public static function providerLabels(): array
    {
        return [
            'gtm' => __('Google Tag Manager (dataLayer)'),
            'ga4' => __('Google Analytics 4 (gtag)'),
            'plausible' => 'Plausible',
            'fathom' => 'Fathom',
            'umami' => 'Umami',
            'matomo' => 'Matomo',
            'custom' => __('Custom'),
        ];
    }

    /**
     * @return array<int, string>
     */
    public function providers(): array
    {
        return array_values(array_intersect(self::PROVIDERS, (array) $this->settings->get('analytics_providers', [])));
    }

    /**
     * What the script is given.
     */
    public function config(): array
    {
        $providers = $this->providers();
        $custom = in_array('custom', $providers, true) ? $this->custom() : null;

        if ($custom === null) {
            $providers = array_values(array_diff($providers, ['custom']));
        }

        return [
            'providers' => $providers,
            'track' => array_values(array_intersect(self::EVENTS, (array) $this->settings->get('analytics_events'))),
            'events' => $this->eventNames(),
            'custom' => $custom,
            'debug' => $this->settings->bool('analytics_debug'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function eventNames(): array
    {
        $names = [];

        foreach (self::EVENTS as $event) {
            $name = trim((string) $this->settings->get('analytics_event_'.$event));
            $names[$event] = static::validEventName($name) ? $name : Settings::defaults()['analytics_event_'.$event];
        }

        return $names;
    }

    /**
     * The custom tracker: a function already on the page, and/or a URL to
     * send each event to. Null when neither is set (or valid).
     */
    public function custom(): ?array
    {
        $function = trim((string) $this->settings->get('analytics_custom_function'));
        $function = static::validFunction($function) ? $function : null;

        $endpoint = trim((string) $this->settings->get('analytics_custom_endpoint'));
        $endpoint = static::validEndpoint($endpoint) ? $endpoint : null;

        if (! $function && ! $endpoint) {
            return null;
        }

        $arguments = $this->settings->get('analytics_custom_arguments');

        return [
            'function' => $function,
            'arguments' => in_array($arguments, self::ARGUMENTS, true) ? $arguments : 'object',
            'properties' => $this->propertyNames(),
            'endpoint' => $endpoint,
        ];
    }

    /**
     * The name each property is sent as, renamed where the settings say.
     *
     * @return array<string, string>
     */
    public function propertyNames(): array
    {
        $renamed = (array) $this->settings->get('analytics_custom_properties', []);
        $names = [];

        foreach (self::PROPERTIES as $property) {
            $name = trim((string) ($renamed[$property] ?? ''));
            $names[$property] = preg_match('/^[A-Za-z_][A-Za-z0-9_.\-]{0,63}$/', $name) ? $name : $property;
        }

        return $names;
    }

    /**
     * The classes Plausible's own script reads on a button, so clicks are
     * counted even before (or without) ours. Ours removes them when it
     * runs, so nothing is counted twice.
     */
    public function plausibleClasses(): ?array
    {
        if (! in_array('plausible', $this->providers(), true) || ! in_array('click', $this->config()['track'], true)) {
            return null;
        }

        return ['event' => 'plausible-event-name='.str_replace(' ', '+', $this->eventNames()['click'])];
    }

    /**
     * A function's name or path on window, such as myTracker.track or
     * _paq.push. Only names: nothing else is run.
     */
    public static function validFunction(?string $function): bool
    {
        return is_string($function) && preg_match('/^[A-Za-z_$][A-Za-z0-9_$]*(\.[A-Za-z_$][A-Za-z0-9_$]*){0,4}$/', $function) === 1;
    }

    public static function validEndpoint(?string $endpoint): bool
    {
        if (! is_string($endpoint) || $endpoint === '') {
            return false;
        }

        if (str_starts_with($endpoint, '/') && ! str_starts_with($endpoint, '//')) {
            return true;
        }

        return filter_var($endpoint, FILTER_VALIDATE_URL) !== false && preg_match('#^https?://#i', $endpoint) === 1;
    }

    public static function validEventName(?string $name): bool
    {
        return is_string($name) && preg_match('/^[\p{L}\p{N}_ .:\-]{1,64}$/u', $name) === 1;
    }
}
