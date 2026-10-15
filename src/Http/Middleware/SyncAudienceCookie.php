<?php

namespace NineteenNinetyFour\PromoBar\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use NineteenNinetyFour\PromoBar\AudienceCookie;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keeps the signed-in cookie in step with the visitor on pages Laravel
 * draws, for people already signed in when the addon was installed and
 * after a change to their groups.
 */
class SyncAudienceCookie
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        app(AudienceCookie::class)->sync($request, $response);

        return $response;
    }
}
