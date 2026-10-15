<?php

namespace NineteenNinetyFour\PromoBar\Listeners;

use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use NineteenNinetyFour\PromoBar\AudienceCookie;
use NineteenNinetyFour\PromoBar\Visitor;
use Statamic\Facades\User;

/**
 * Sets the signed-in cookie when someone signs in, and removes it when
 * they sign out.
 */
class UpdateAudienceCookie
{
    public function __construct(protected AudienceCookie $cookie) {}

    public function handle(Login|Logout $event): void
    {
        if ($event instanceof Logout) {
            $this->cookie->queue(new Visitor(false));

            return;
        }

        $user = User::fromUser($event->user);

        $this->cookie->queue(new Visitor(true, $user ? $user->groups()->map->handle()->values()->all() : []));
    }
}
