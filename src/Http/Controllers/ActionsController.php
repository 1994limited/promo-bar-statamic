<?php

namespace NineteenNinetyFour\PromoBar\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use NineteenNinetyFour\PromoBar\Integrations\Cargo;
use NineteenNinetyFour\PromoBar\Presenter;
use NineteenNinetyFour\PromoBar\PromoBars;
use NineteenNinetyFour\PromoBar\Styles;

/**
 * At /!/promo-bar: the stylesheet, the script, and a visitor's free
 * delivery progress.
 */
class ActionsController extends Controller
{
    public function stylesheet(Styles $styles, PromoBars $bars): Response
    {
        return $this->asset($styles->css($bars->liveAnywhere()), 'text/css');
    }

    public function script(): Response
    {
        return $this->asset((string) file_get_contents(__DIR__.'/../../../resources/frontend/promo-bar.js'), 'application/javascript');
    }

    /**
     * How far the visitor's basket is from each bar's free delivery. Never
     * cached: it's theirs. Without a basket a bar's entry is null.
     */
    public function state(Request $request, PromoBars $bars, Presenter $presenter, Cargo $cargo): JsonResponse
    {
        $ids = collect(explode(',', (string) $request->query('bars', '')))
            ->map(fn ($id) => trim($id))
            ->filter(fn ($id) => preg_match('/^[A-Za-z0-9\-]{1,64}$/', $id))
            ->unique()
            ->take(20);

        $subtotal = $cargo->subtotal();
        $state = [];

        foreach ($ids as $id) {
            $bar = $bars->find($id);

            $state[$id] = $bar && $bar->get('free_delivery') && $bars->isLive($bar)
                ? $presenter->freeDeliveryState($bar, $subtotal)
                : null;
        }

        return response()->json(['bars' => $state])->withHeaders([
            'Cache-Control' => 'no-store, private',
            'X-Robots-Tag' => 'noindex',
        ]);
    }

    /**
     * A file whose URL changes with its contents (?v=), so browsers keep it.
     */
    protected function asset(string $contents, string $type): Response
    {
        return response($contents, 200, [
            'Content-Type' => $type.'; charset=UTF-8',
            'Cache-Control' => request()->has('v') ? 'public, max-age=31536000, immutable' : 'public, max-age=300',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
