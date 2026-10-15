<?php

namespace NineteenNinetyFour\PromoBar\Tests\Feature;

use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use NineteenNinetyFour\PromoBar\Tests\TestCase;

class TagsTest extends TestCase
{
    public function test_nothing_is_printed_without_live_bars(): void
    {
        $this->assertSame('', trim($this->antlers('{{ promo_bar }}')));

        $this->bar(['enabled' => false]);

        $this->assertSame('', trim($this->antlers('{{ promo_bar }}')));
    }

    public function test_a_bar_with_its_message_stylesheet_and_script(): void
    {
        $this->bar();

        $html = $this->antlers('{{ promo_bar }}');

        $this->assertStringContainsString('<section id="promo-bar-1" class="promo-bar promo-bar--first" aria-label="Announcements" data-promo-bar-group', $html);
        $this->assertStringContainsString('Free delivery this weekend', $html);
        $this->assertStringContainsString('<link rel="stylesheet" href="http://localhost/!/promo-bar/promo-bar.css?v=', $html);
        $this->assertStringContainsString('<script src="http://localhost/!/promo-bar/promo-bar.js?v=', $html);
        $this->assertStringContainsString('<script type="application/json" id="promo-bar-config">', $html);
        $this->assertStringContainsString('promoBarEvaluate(g)', $html);
        $this->assertStringContainsString('promo-bar--solid promo-bar--themed promo-bar--button-filled', $html);
    }

    public function test_the_message_keeps_its_formatting(): void
    {
        $this->bar(['message' => [['type' => 'text', 'text' => 'Sale'], ['type' => 'text', 'marks' => [['type' => 'bold']], 'text' => ' now on']]]);

        $this->assertStringContainsString('Sale<strong> now on</strong>', $this->antlers('{{ promo_bar }}'));
    }

    public function test_text_is_escaped(): void
    {
        $this->bar([
            'title' => '"><script>alert(1)</script>',
            'message' => [['type' => 'text', 'text' => '<img src=x onerror=alert(1)>']],
            'cta_label' => '<b>Go</b>',
            'cta_url' => '/shop?a=1&b=2',
            'code_source' => 'text',
            'code' => '<i>X</i>',
        ]);

        $html = $this->antlers('{{ promo_bar }}');

        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringNotContainsString('<img src=x', $html);
        $this->assertStringNotContainsString('<b>Go</b>', $html);
        $this->assertStringNotContainsString('<i>X</i>', $html);
        $this->assertStringContainsString('href="/shop?a=1&amp;b=2"', $html);
    }

    public function test_the_message_is_sanitised_whatever_was_stored(): void
    {
        $this->bar(['message' => [
            ['type' => 'text', 'marks' => [['type' => 'link', 'attrs' => ['href' => 'javascript:alert(1)']]], 'text' => 'bad link'],
            ['type' => 'text', 'marks' => [['type' => 'link', 'attrs' => ['href' => '/shop', 'target' => '_blank']]], 'text' => 'good link'],
            ['type' => 'html', 'attrs' => ['html' => '<img src=x onerror=alert(1)>']],
            ['type' => 'text', 'marks' => [['type' => 'bold']], 'text' => 'bold'],
        ]]);

        $html = $this->antlers('{{ promo_bar }}');

        $this->assertStringNotContainsString('javascript:', $html);
        $this->assertStringNotContainsString('onerror', $html);
        $this->assertStringContainsString('<a href="/shop" target="_blank" rel="noopener">good link</a>', $html);
        $this->assertStringContainsString('<strong>bold</strong>', $html);
    }

    public function test_the_button(): void
    {
        $this->bar(['cta_label' => 'Shop now', 'cta_url' => 'https://example.com/sale', 'cta_new_tab' => true]);

        $html = $this->antlers('{{ promo_bar }}');

        $this->assertStringContainsString('<a class="promo-bar__cta" href="https://example.com/sale" data-promo-bar-cta target="_blank" rel="noopener">Shop now<span class="promo-bar__visually-hidden"> (opens in a new tab)</span></a>', $html);
    }

    public function test_the_button_links_to_an_entry(): void
    {
        $this->page('sale', 'sale');
        $this->bar(['cta_label' => 'See the sale', 'cta_url' => 'entry::sale']);

        $this->assertStringContainsString('href="/sale"', $this->antlers('{{ promo_bar }}'));
    }

    public function test_unsafe_links_are_left_out(): void
    {
        $this->bar(['cta_label' => 'Click', 'cta_url' => 'javascript:alert(1)']);

        $html = $this->antlers('{{ promo_bar }}');

        $this->assertStringNotContainsString('javascript:', $html);
        $this->assertStringNotContainsString('promo-bar__cta', $html);
    }

    public function test_a_code_with_its_copy_button_hidden_until_the_script_runs(): void
    {
        $this->bar(['code_source' => 'text', 'code' => 'SAVE10']);

        $html = $this->antlers('{{ promo_bar }}');

        $this->assertStringContainsString('<code data-promo-bar-code>SAVE10</code>', $html);
        $this->assertMatchesRegularExpression('/<button type="button" class="promo-bar__copy"[^>]+hidden>Copy<\/button>/', $html);
    }

    public function test_a_countdown_shows_the_end_date_without_javascript(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-15 12:00', 'Europe/London'));
        $this->bar(['countdown' => true, 'ends_at' => '2026-10-18 23:59']);

        $html = $this->antlers('{{ promo_bar }}');

        $this->assertStringContainsString('data-ends="2026-10-18T23:59:00+01:00"', $html);
        $this->assertStringContainsString('<span data-promo-bar-fallback>Ends <time datetime="2026-10-18T23:59:00+01:00">Sun 18 Oct, 23:59</time></span>', $html);
        $this->assertStringContainsString('<span data-promo-bar-js="countdown" aria-hidden="true" hidden>Ends in <span data-promo-bar-remaining></span></span>', $html);
    }

    public function test_dismissing(): void
    {
        $bar = $this->bar(['dismiss_days' => 7]);

        $html = $this->antlers('{{ promo_bar }}');

        $this->assertStringContainsString('data-promo-bar-key="'.$bar->id().':'.$bar->contentHash().'"', $html);
        $this->assertStringContainsString('data-dismiss-days="7"', $html);
        $this->assertMatchesRegularExpression('/class="promo-bar__dismiss" data-promo-bar-js="dismiss" data-promo-bar-dismiss aria-label="Dismiss: Weekend" hidden>/', $html);
    }

    public function test_a_bar_that_cant_be_dismissed_has_no_button(): void
    {
        $this->bar(['dismissible' => false]);

        $this->assertStringNotContainsString('promo-bar__dismiss', $this->antlers('{{ promo_bar }}'));
    }

    public function test_scheduled_and_ended_bars_arent_on_the_page(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-15 12:00', 'Europe/London'));

        $this->bar(['title' => 'Later', 'starts_at' => '2026-11-27 00:00'], 'Black Friday secret');
        $this->bar(['title' => 'Gone', 'ends_at' => '2026-10-01 00:00'], 'Summer sale');
        $this->bar(['title' => 'Now'], 'Autumn sale');

        $html = $this->antlers('{{ promo_bar }}');

        $this->assertStringContainsString('Autumn sale', $html);
        $this->assertStringNotContainsString('Black Friday secret', $html);
        $this->assertStringNotContainsString('Summer sale', $html);

        $this->travelTo(CarbonImmutable::parse('2026-11-27 00:00', 'Europe/London'));

        $this->assertStringContainsString('Black Friday secret', $this->antlers('{{ promo_bar }}'));
    }

    public function test_first_mode_shows_the_first_and_hides_the_rest(): void
    {
        $this->bar(['title' => 'One'], 'First bar');
        $this->bar(['title' => 'Two'], 'Second bar');

        $html = $this->antlers('{{ promo_bar }}');

        $this->assertLessThan(strpos($html, 'Second bar'), strpos($html, 'First bar'));
        $this->assertMatchesRegularExpression('/data-promo-bar-name="Two"[^>]* hidden>/', $html);
        $this->assertDoesNotMatchRegularExpression('/data-promo-bar-name="One"[^>]* hidden>/', $html);
    }

    public function test_stack_mode_shows_them_all(): void
    {
        $this->bar(['title' => 'One']);
        $this->bar(['title' => 'Two']);

        $html = $this->antlers('{{ promo_bar mode="stack" }}');

        $this->assertStringContainsString('promo-bar--stack', $html);
        $this->assertDoesNotMatchRegularExpression('/data-promo-bar-name="Two"[^>]* hidden>/', $html);
    }

    public function test_rotate_mode_keeps_the_others_in_place_but_inert(): void
    {
        $this->bar(['title' => 'One']);
        $this->bar(['title' => 'Two']);

        $html = $this->antlers('{{ promo_bar mode="rotate" }}');

        $this->assertStringContainsString('data-mode="rotate" data-interval="6"', $html);
        $this->assertMatchesRegularExpression('/is-inactive" data-promo-bar data-promo-bar-id="[^"]+" data-promo-bar-name="Two"[^>]* inert>/', $html);
        $this->assertStringContainsString('data-promo-bar-previous aria-label="Previous"', $html);
        $this->assertStringContainsString('data-promo-bar-pause aria-label="Pause"', $html);
    }

    public function test_the_mode_comes_from_the_settings(): void
    {
        $this->settings(['display' => 'stack']);
        $this->bar();

        $this->assertStringContainsString('data-mode="stack"', $this->antlers('{{ promo_bar }}'));
    }

    public function test_limit_sticky_class_and_label(): void
    {
        $this->bar(['title' => 'One']);
        $this->bar(['title' => 'Two']);

        $html = $this->antlers('{{ promo_bar mode="stack" limit="1" sticky="true" class="site-promos" label="Offers" }}');

        $this->assertStringContainsString('class="promo-bar promo-bar--stack site-promos" aria-label="Offers"', $html);
        $this->assertStringContainsString(' data-sticky>', $html);
        $this->assertStringNotContainsString('data-promo-bar-name="Two"', $html);
    }

    public function test_on_cached_pages_the_limit_is_left_to_the_browser(): void
    {
        $this->staticCaching();
        $this->bar(['title' => 'Members', 'audience' => 'signed_in']);
        $this->bar(['title' => 'Everyone']);

        $html = $this->antlers('{{ promo_bar mode="stack" limit="1" }}');

        // A guest's browser drops the members' bar and shows the next one.
        $this->assertStringContainsString('data-promo-bar-name="Everyone"', $html);
        $this->assertStringContainsString('data-limit="1"', $html);
    }

    public function test_assets_in_the_head_are_printed_once(): void
    {
        $this->bar();

        $html = $this->antlers('<head>{{ promo_bar:assets }}</head><body>{{ promo_bar }}{{ promo_bar }}</body>');

        $this->assertSame(1, substr_count($html, 'promo-bar.css'));
        $this->assertSame(1, substr_count($html, 'promo-bar.js'));
        $this->assertLessThan(strpos($html, '<body>'), strpos($html, 'promo-bar.css'));
        $this->assertSame(2, substr_count($html, 'data-promo-bar-group data-mode'));
        $this->assertStringContainsString('id="promo-bar-2"', $html);
    }

    public function test_assets_can_be_left_out(): void
    {
        $this->bar();

        $this->assertStringNotContainsString('promo-bar.css', $this->antlers('{{ promo_bar assets="false" }}'));
    }

    public function test_a_csp_nonce(): void
    {
        $this->bar();

        $this->assertSame(2, substr_count($this->antlers('{{ promo_bar nonce="abc123" }}'), 'nonce="abc123"'));

        Vite::useCspNonce('from-vite');

        $this->assertStringContainsString('<script nonce="from-vite">', $this->antlers('{{ promo_bar }}'));
    }

    public function test_bars_only_on_some_pages(): void
    {
        $this->page('about', 'about');
        $this->product('mug', 'mug');
        $this->bar(['pages' => 'only', 'collections' => ['products']], 'Shop bar');

        $this->assertStringContainsString('Shop bar', $this->antlers('{{ promo_bar }}', ['id' => 'mug']));
        $this->assertStringNotContainsString('Shop bar', $this->antlers('{{ promo_bar }}', ['id' => 'about']));
    }

    public function test_url_patterns_use_the_request(): void
    {
        $this->bar(['pages' => 'except', 'urls' => '/checkout*'], 'Not at checkout');

        $this->get('/checkout/pay');
        $this->app->instance('request', Request::create('/checkout/pay'));
        $this->assertStringNotContainsString('Not at checkout', $this->antlers('{{ promo_bar }}'));

        $this->app->instance('request', Request::create('/shop'));
        $this->assertStringContainsString('Not at checkout', $this->antlers('{{ promo_bar }}'));
    }

    public function test_audience_bars_are_settled_in_the_browser_when_pages_may_be_cached(): void
    {
        $this->staticCaching();
        $this->bar(['title' => 'Members', 'audience' => 'signed_in', 'groups' => ['vip']]);

        $html = $this->antlers('{{ promo_bar }}');

        $this->assertMatchesRegularExpression('/data-promo-bar-name="Members"[^>]* data-audience="signed_in" data-groups="vip"[^>]* hidden>/', $html);
    }

    public function test_audience_bars_are_settled_on_the_server_without_static_caching(): void
    {
        $this->bar(['title' => 'Members', 'audience' => 'signed_in', 'groups' => ['vip']], 'For members');
        $this->bar(['title' => 'Guests', 'audience' => 'guests'], 'For guests');

        $guest = $this->antlers('{{ promo_bar mode="stack" }}');
        $this->assertStringContainsString('For guests', $guest);
        $this->assertStringNotContainsString('For members', $guest);
        $this->assertStringNotContainsString('data-audience="', $guest);

        $this->actingAs($this->user('ada@example.com'));
        $this->assertStringNotContainsString('For members', $this->antlers('{{ promo_bar mode="stack" }}'));

        $this->actingAs($this->user('vip@example.com', groups: ['vip']));
        $member = $this->antlers('{{ promo_bar mode="stack" }}');
        $this->assertStringContainsString('For members', $member);
        $this->assertStringNotContainsString('For guests', $member);
    }
}
