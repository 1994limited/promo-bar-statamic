<?php

namespace NineteenNinetyFour\PromoBar\Stache;

use Illuminate\Support\Collection;
use NineteenNinetyFour\PromoBar\Bar;
use NineteenNinetyFour\PromoBar\Contracts\BarRepository;
use Statamic\Facades\Stache;

class StacheBarRepository implements BarRepository
{
    protected function store(): BarsStore
    {
        return Stache::store('promo-bars');
    }

    public function all(): Collection
    {
        return $this->store()->getItems($this->store()->paths()->keys())
            ->filter()
            ->sortBy([
                fn (Bar $a, Bar $b) => $a->order() <=> $b->order(),
                fn (Bar $a, Bar $b) => ($a->createdAt()?->getTimestamp() ?? 0) <=> ($b->createdAt()?->getTimestamp() ?? 0),
            ])
            ->values();
    }

    public function find(string $id): ?Bar
    {
        return $this->store()->getItem($id);
    }

    public function save(Bar $bar): void
    {
        $this->store()->save($bar);
    }

    public function delete(Bar $bar): void
    {
        $this->store()->delete($bar);
    }
}
