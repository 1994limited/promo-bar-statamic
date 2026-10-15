<?php

namespace NineteenNinetyFour\PromoBar\Tags;

use NineteenNinetyFour\PromoBar\Page;
use NineteenNinetyFour\PromoBar\Renderer;
use Statamic\Fields\Value;
use Statamic\Tags\Tags;

class PromoBarTags extends Tags
{
    protected static $handle = 'promo_bar';

    /**
     * {{ promo_bar }}: the bars live on this page, with the stylesheet and
     * script the first time on a page.
     *
     * Parameters: mode (first, stack or rotate), limit, sticky, class,
     * label, assets (false when {{ promo_bar:assets }} is in <head>), nonce.
     */
    public function index(): string
    {
        return app(Renderer::class)->render($this->page(), [
            'mode' => $this->params->get('mode'),
            'limit' => $this->params->int('limit') ?: null,
            'sticky' => $this->params->has('sticky') ? $this->params->bool('sticky') : null,
            'class' => $this->params->get('class'),
            'label' => $this->params->get('label'),
            'assets' => $this->params->bool('assets', true),
            'nonce' => $this->params->get('nonce'),
        ]);
    }

    /**
     * {{ promo_bar:assets }} in <head>: the stylesheet and script, so they
     * load early. Then {{ promo_bar }} prints only the bars.
     */
    public function assets(): string
    {
        return app(Renderer::class)->assets($this->params->get('nonce'));
    }

    protected function page(): Page
    {
        $id = $this->context->get('id');

        if ($id instanceof Value) {
            $id = $id->raw();
        }

        return Page::current(request(), is_string($id) ? $id : null);
    }
}
