<?php

namespace NineteenNinetyFour\PromoBar;

use Statamic\Facades\User;

/**
 * Who's looking, for pages drawn for one visitor (not statically cached):
 * whether they're signed in and their user groups.
 */
class Visitor
{
    /**
     * @param  array<int, string>  $groups
     */
    public function __construct(public readonly bool $signedIn = false, public readonly array $groups = []) {}

    public static function current(): static
    {
        $user = User::current();

        return new static($user !== null, $user ? $user->groups()->map->handle()->values()->all() : []);
    }

    public function sees(Bar $bar): bool
    {
        return match ($bar->get('audience')) {
            'guests' => ! $this->signedIn,
            'signed_in' => $this->signedIn && $this->inGroups($bar),
            default => true,
        };
    }

    protected function inGroups(Bar $bar): bool
    {
        $groups = array_filter((array) $bar->get('groups', []), 'is_string');

        return $groups === [] || array_intersect($groups, $this->groups) !== [];
    }

    /**
     * The audience cookie's value: "1", then the groups after a colon.
     */
    public function cookieValue(): ?string
    {
        if (! $this->signedIn) {
            return null;
        }

        return $this->groups === [] ? '1' : '1:'.implode(',', $this->groups);
    }
}
