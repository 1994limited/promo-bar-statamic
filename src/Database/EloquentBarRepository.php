<?php

namespace NineteenNinetyFour\PromoBar\Database;

use Illuminate\Support\Collection;
use NineteenNinetyFour\PromoBar\Bar;
use NineteenNinetyFour\PromoBar\Contracts\BarRepository;

class EloquentBarRepository implements BarRepository
{
    public function all(): Collection
    {
        return BarModel::query()->orderBy('order')->orderBy('created_at')->get()
            ->map(fn (BarModel $model) => $this->fromModel($model))
            ->values();
    }

    public function find(string $id): ?Bar
    {
        $model = BarModel::query()->find($id);

        return $model ? $this->fromModel($model) : null;
    }

    public function save(Bar $bar): void
    {
        BarModel::query()->updateOrCreate(['id' => $bar->id()], [
            'order' => $bar->order(),
            'data' => $bar->data(),
            'created_at' => $bar->createdAt(),
            'updated_at' => $bar->updatedAt(),
        ]);
    }

    public function delete(Bar $bar): void
    {
        BarModel::query()->whereKey($bar->id())->delete();
    }

    protected function fromModel(BarModel $model): Bar
    {
        return Bar::fromArray($model->getKey(), [
            ...($model->data ?? []),
            'order' => $model->order,
            'created_at' => $model->created_at?->toIso8601String(),
            'updated_at' => $model->updated_at?->toIso8601String(),
        ]);
    }
}
