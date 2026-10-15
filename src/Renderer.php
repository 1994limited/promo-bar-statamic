<?php

namespace NineteenNinetyFour\PromoBar;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Vite;
use Statamic\Facades\Site;

/**
 * Draws the live bars for a page: the markup, and (once a page) the
 * stylesheet, the script and its settings.
 *
 * Pages may be statically cached, so the markup is the same for every
 * visitor. What depends on the visitor (signed in or not, dismissed bars,
 * their basket) is settled in the browser. Without static caching it's
 * settled here instead, and the page is right without JavaScript.
 */
class Renderer
{
    public const MODES = ['first', 'stack', 'rotate'];

    protected bool $assetsRendered = false;

    protected int $groups = 0;

    public function __construct(
        protected PromoBars $bars,
        protected Presenter $presenter,
        protected Settings $settings,
        protected Styles $styles,
        protected Analytics $analytics,
    ) {}

    /**
     * @param  array{mode?: string|null, limit?: int|null, sticky?: bool|null, class?: string|null, label?: string|null, assets?: bool, nonce?: string|null}  $options
     */
    public function render(Page $page, array $options = [], ?CarbonImmutable $now = null): string
    {
        $now ??= CarbonImmutable::now();
        $cached = static::pagesMayBeCached();
        $visitor = $cached ? null : Visitor::current();

        $bars = $this->bars->live($page, $now);

        if ($visitor) {
            $bars = $bars->filter(fn (Bar $bar) => $visitor->sees($bar))->values();
        }

        if (is_numeric($options['limit'] ?? null) && (int) $options['limit'] > 0) {
            $bars = $bars->take((int) $options['limit'])->values();
        }

        if ($bars->isEmpty()) {
            return '';
        }

        $mode = in_array($options['mode'] ?? null, self::MODES, true) ? $options['mode'] : $this->settings->get('display');
        $mode = in_array($mode, self::MODES, true) ? $mode : 'first';

        $presented = $bars->map(fn (Bar $bar) => $this->presenter->bar($bar, $visitor, $now))->values()->all();

        if ($visitor) {
            // Settled here: the browser mustn't second-guess it from a cookie.
            $presented = array_map(fn ($bar) => [...$bar, 'audience' => null, 'groups' => ''], $presented);
        }

        $nonce = $options['nonce'] ?? Vite::cspNonce();
        $assets = ($options['assets'] ?? true) && ! $this->assetsRendered;

        $html = ($assets ? $this->assets($nonce) : '').view('promo-bar::bars', [
            'id' => 'promo-bar-'.(++$this->groups),
            'bars' => $presented,
            'mode' => $mode,
            'interval' => max(2, (int) $this->settings->get('rotate_interval')),
            'sticky' => (bool) ($options['sticky'] ?? $this->settings->bool('sticky')),
            'class' => $options['class'] ?? null,
            'label' => $options['label'] ?? $this->settings->text('region_label'),
            'labels' => $this->labels(),
            'cookie' => config('promo-bar.audience_cookie.name', 'promo_bar_audience'),
            'cached' => $cached,
            'plausible' => $this->analytics->plausibleClasses(),
        ])->render().$this->bootstrap($nonce);

        return $html;
    }

    /**
     * The stylesheet, the script's settings and the script. Printed with
     * the first bars on a page, or by {{ promo_bar:assets }} in <head>.
     */
    public function assets(?string $nonce = null): string
    {
        if ($this->assetsRendered) {
            return '';
        }

        $this->assetsRendered = true;
        $nonce ??= Vite::cspNonce();
        $nonceAttribute = $nonce ? ' nonce="'.e($nonce).'"' : '';

        $config = json_encode([
            'endpoint' => url('!/promo-bar'),
            'cookie' => config('promo-bar.audience_cookie.name', 'promo_bar_audience'),
            'site' => Site::current()->handle(),
            'labels' => $this->labels(),
            'analytics' => $this->analytics->config(),
        ], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES);

        return '<link rel="stylesheet" href="'.e($this->stylesheetUrl()).'">'
            .'<script type="application/json" id="promo-bar-config">'.$config.'</script>'
            .'<script src="'.e(static::scriptUrl()).'" defer'.$nonceAttribute.'></script>';
    }

    /**
     * Run straight after the bars, before the browser paints them: hides
     * what this visitor has dismissed or isn't for them, so nothing jumps.
     */
    public function bootstrap(?string $nonce = null): string
    {
        $nonceAttribute = $nonce ? ' nonce="'.e($nonce).'"' : '';

        return '<script'.$nonceAttribute.'>'.static::bootstrapScript().'</script>';
    }

    public static function bootstrapScript(): string
    {
        static $script = null;

        if ($script !== null) {
            return $script;
        }

        $source = (string) file_get_contents(__DIR__.'/../resources/frontend/promo-bar.js');
        preg_match('#/\* bootstrap:start \*/(.*?)/\* bootstrap:end \*/#s', $source, $match);

        // Comments and indentation out: it's printed on every page.
        $code = preg_replace('#^\s*//.*$#m', '', $match[1] ?? '');
        $code = preg_replace('#/\*.*?\*/#s', '', $code);
        $code = preg_replace('/\n\s*/', "\n", trim($code));

        return $script = '(function(){'.$code."\nvar s=document.currentScript,g=s&&s.previousElementSibling;if(g&&g.hasAttribute('data-promo-bar-group'))promoBarEvaluate(g);})();";
    }

    /**
     * The words the bars and script use, from the settings.
     */
    public function labels(): array
    {
        return [
            'dismiss' => $this->settings->text('dismiss_label'),
            'copy' => $this->settings->text('copy_label'),
            'copied' => $this->settings->text('copied_label'),
            'previous' => $this->settings->text('previous_label'),
            'next' => $this->settings->text('next_label'),
            'pause' => $this->settings->text('pause_label'),
            'play' => $this->settings->text('play_label'),
            'units' => ['d' => __('d'), 'h' => __('h'), 'm' => __('m'), 's' => __('s')],
        ];
    }

    public function stylesheetUrl(): string
    {
        return url('!/promo-bar/promo-bar.css').'?v='.$this->styles->version($this->bars->liveAnywhere());
    }

    public static function scriptUrl(): string
    {
        return url('!/promo-bar/promo-bar.js').'?v='.substr(md5_file(__DIR__.'/../resources/frontend/promo-bar.js'), 0, 12);
    }

    /**
     * Whether pages may be statically cached (any strategy), so the same
     * markup is served to everyone.
     */
    public static function pagesMayBeCached(): bool
    {
        return filled(config('statamic.static_caching.strategy'));
    }

    /**
     * Forget what's been printed, as for a new request.
     */
    public function reset(): void
    {
        $this->assetsRendered = false;
        $this->groups = 0;
    }
}
