<?php

namespace NineteenNinetyFour\PromoBar;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Symfony\Component\HttpFoundation\Response;

/**
 * The signed-in cookie the script reads to pick bars for signed-in
 * visitors (or guests only) on cached pages: "1", or "1:" and the user's
 * groups. It isn't secret and doesn't sign anyone in, so it's readable by
 * the script and not encrypted.
 */
class AudienceCookie
{
    public function name(): string
    {
        return (string) config('promo-bar.audience_cookie.name', 'promo_bar_audience');
    }

    public function queue(Visitor $visitor): void
    {
        $value = $visitor->cookieValue();

        $value === null
            ? Cookie::queue(Cookie::forget($this->name()))
            : Cookie::queue($this->make($value));
    }

    public function sync(Request $request, Response $response): void
    {
        if (! $this->wanted() || ! method_exists($response->headers, 'setCookie')) {
            return;
        }

        $value = Visitor::current()->cookieValue();

        if ($request->cookies->get($this->name()) === $value) {
            return;
        }

        $response->headers->setCookie($value === null ? Cookie::forget($this->name()) : $this->make($value));
    }

    /**
     * Only worth setting when there are bars that need it.
     */
    public function wanted(): bool
    {
        return app(PromoBars::class)->all()->contains(fn (Bar $bar) => in_array($bar->get('audience'), ['guests', 'signed_in'], true));
    }

    protected function make(string $value): \Symfony\Component\HttpFoundation\Cookie
    {
        return Cookie::make($this->name(), $value, (int) config('promo-bar.audience_cookie.minutes', 43200), httpOnly: false, sameSite: 'lax');
    }
}
