<?php

namespace NineteenNinetyFour\PromoBar\Tests\Feature;

use NineteenNinetyFour\PromoBar\Analytics;
use NineteenNinetyFour\PromoBar\Tests\TestCase;

class AnalyticsTest extends TestCase
{
    protected function config(): array
    {
        $this->bar();
        preg_match('#<script type="application/json" id="promo-bar-config">(.*?)</script>#', $this->antlers('{{ promo_bar }}'), $match);

        return json_decode($match[1], true)['analytics'];
    }

    public function test_nothing_is_sent_by_default_but_clicks_dismissals_and_copies_are_ready(): void
    {
        $config = $this->config();

        $this->assertSame([], $config['providers']);
        $this->assertSame(['click', 'dismiss', 'copy'], $config['track']);
        $this->assertSame('promo_bar_click', $config['events']['click']);
        $this->assertNull($config['custom']);
    }

    public function test_providers_and_event_names_from_the_settings(): void
    {
        $this->settings([
            'analytics_providers' => ['gtm', 'plausible', 'nonsense'],
            'analytics_events' => ['view', 'click'],
            'analytics_event_click' => 'Promo Click',
            'analytics_event_view' => '<script>',
        ]);

        $config = $this->config();

        $this->assertSame(['gtm', 'plausible'], $config['providers']);
        $this->assertSame(['view', 'click'], $config['track']);
        $this->assertSame('Promo Click', $config['events']['click']);
        $this->assertSame('promo_bar_view', $config['events']['view']);
    }

    public function test_a_custom_function_and_endpoint(): void
    {
        $this->settings([
            'analytics_providers' => ['custom'],
            'analytics_custom_function' => 'myTracker.track',
            'analytics_custom_arguments' => 'array',
            'analytics_custom_properties' => ['id' => 'campaign_id', 'name' => 'bad name!'],
            'analytics_custom_endpoint' => '/collect',
        ]);

        $custom = $this->config()['custom'];

        $this->assertSame('myTracker.track', $custom['function']);
        $this->assertSame('array', $custom['arguments']);
        $this->assertSame('campaign_id', $custom['properties']['id']);
        $this->assertSame('name', $custom['properties']['name']);
        $this->assertSame('/collect', $custom['endpoint']);
    }

    public function test_custom_takes_only_a_function_name_never_code(): void
    {
        $this->settings(['analytics_providers' => ['custom'], 'analytics_custom_function' => 'alert(document.cookie)', 'analytics_custom_endpoint' => 'javascript:alert(1)']);

        $config = $this->config();

        $this->assertNull($config['custom']);
        $this->assertSame([], $config['providers']);
    }

    public function test_function_and_endpoint_checks(): void
    {
        $this->assertTrue(Analytics::validFunction('_paq.push'));
        $this->assertTrue(Analytics::validFunction('window.$tracker'));
        $this->assertFalse(Analytics::validFunction('a;b'));
        $this->assertFalse(Analytics::validFunction('a.b()'));
        $this->assertTrue(Analytics::validEndpoint('https://example.com/e'));
        $this->assertFalse(Analytics::validEndpoint('//evil.example'));
        $this->assertFalse(Analytics::validEndpoint('ftp://example.com'));
    }

    public function test_plausible_tags_the_button_for_its_own_script(): void
    {
        $this->settings(['analytics_providers' => ['plausible']]);
        $this->bar(['title' => 'Summer sale', 'cta_label' => 'Shop', 'cta_url' => '/shop']);

        $this->assertStringContainsString('class="promo-bar__cta plausible-event-name=promo_bar_click plausible-event-bar=Summer+sale"', $this->antlers('{{ promo_bar }}'));
    }

    public function test_the_config_cant_break_out_of_its_script_tag(): void
    {
        $this->settings(['dismiss_label' => '</script><script>alert(1)</script>']);
        $this->bar();

        $this->assertStringNotContainsString('</script><script>alert(1)', $this->antlers('{{ promo_bar }}'));
    }
}
