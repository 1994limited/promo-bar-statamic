<?php

namespace NineteenNinetyFour\PromoBar\Contracts;

use Illuminate\Support\Collection;
use NineteenNinetyFour\PromoBar\Bar;

/**
 * Where bars are kept. The file and database drivers implement it; bind
 * your own in a service provider for anything else.
 */
interface BarRepository
{
    /**
     * Every bar, in priority order (first is highest).
     *
     * @return Collection<int, Bar>
     */
    public function all(): Collection;

    public function find(string $id): ?Bar;

    public function save(Bar $bar): void;

    public function delete(Bar $bar): void;
}
