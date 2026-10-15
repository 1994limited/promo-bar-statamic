<?php

namespace NineteenNinetyFour\PromoBar\Tests\Feature;

use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Route;
use NineteenNinetyFour\PromoBar\Tests\TestCase;

class AudienceCookieTest extends TestCase
{
    protected function queued(): ?\Symfony\Component\HttpFoundation\Cookie
    {
        return Cookie::queued('promo_bar_audience');
    }

    public function test_signing_in_sets_it_with_the_users_groups(): void
    {
        $this->bar(['audience' => 'signed_in']);
        $user = $this->user('vip@example.com', groups: ['vip', 'trade']);

        event(new Login('web', $user, false));

        $this->assertSame('1:vip,trade', $this->queued()->getValue());
        $this->assertFalse($this->queued()->isHttpOnly());
    }

    public function test_signing_in_sets_nothing_when_no_bar_needs_it(): void
    {
        $this->bar();

        event(new Login('web', $this->user(), false));

        $this->assertNull($this->queued());
    }

    public function test_signing_out_removes_it(): void
    {
        event(new Logout('web', $this->user()));

        $this->assertTrue($this->queued()->isCleared());
    }

    public function test_pages_put_it_right_for_people_already_signed_in(): void
    {
        $this->bar(['audience' => 'signed_in']);
        Route::middleware('statamic.web')->get('/plain-test', fn () => 'ok');

        $response = $this->actingAs($this->user())->get('/plain-test');
        $response->assertCookie('promo_bar_audience', '1', encrypted: false);

        $this->withUnencryptedCookie('promo_bar_audience', '1')->actingAs($this->user())->get('/plain-test')->assertCookieMissing('promo_bar_audience');
    }

    public function test_its_left_alone_when_no_bar_needs_it(): void
    {
        $this->bar();
        Route::middleware('statamic.web')->get('/plain-test', fn () => 'ok');

        $this->actingAs($this->user())->get('/plain-test')->assertCookieMissing('promo_bar_audience');
    }
}
