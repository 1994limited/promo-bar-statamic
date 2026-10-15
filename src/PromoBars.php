<?php

namespace NineteenNinetyFour\PromoBar;

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use NineteenNinetyFour\PromoBar\Contracts\BarRepository;
use NineteenNinetyFour\PromoBar\Events\BarDeleted;
use NineteenNinetyFour\PromoBar\Events\BarSaved;
use NineteenNinetyFour\PromoBar\Events\BarsReordered;
use NineteenNinetyFour\PromoBar\Integrations\Cargo;

/**
 * Promo bars: finding, saving and ordering them, and which are live on a
 * page. Resolve it from the container (`app(PromoBars::class)`).
 */
class PromoBars
{
    public function __construct(
        protected BarRepository $repository,
        protected Targeting $targeting,
        protected Cargo $cargo,
    ) {}

    /**
     * Every bar, highest priority first.
     *
     * @return Collection<int, Bar>
     */
    public function all(): Collection
    {
        return $this->repository->all();
    }

    public function find(string $id): ?Bar
    {
        return $this->repository->find($id);
    }

    public function save(Bar $bar): Bar
    {
        $original = $bar->original() ?? $this->repository->find($bar->id())?->original();

        if (! $original && ! $this->repository->find($bar->id())) {
            $bar->order(($this->all()->max(fn (Bar $bar) => $bar->order()) ?? -1) + 1);
        }

        $this->repository->save($bar->touch());
        $bar->syncOriginal();

        BarSaved::dispatch($bar, $original);

        return $bar;
    }

    public function delete(Bar $bar): void
    {
        $this->repository->delete($bar);

        BarDeleted::dispatch($bar);
    }

    /**
     * Put bars in this order, highest priority first. Bars not listed keep
     * their place after them.
     *
     * @param  array<int, string>  $ids
     */
    public function reorder(array $ids): void
    {
        $bars = $this->all()->keyBy(fn (Bar $bar) => $bar->id());
        $ordered = collect($ids)->filter(fn ($id) => $bars->has($id))->unique()->values();
        $ordered = $ordered->merge($bars->keys()->diff($ordered))->values();

        foreach ($ordered as $position => $id) {
            $bar = $bars->get($id);

            if ($bar->order() !== $position) {
                $this->repository->save($bar->order($position));
            }
        }

        BarsReordered::dispatch($ordered->all());
    }

    /**
     * The bars that show on a page now, highest priority first, before the
     * visitor is considered (signed in or not, what they've dismissed).
     *
     * @return Collection<int, Bar>
     */
    public function live(Page $page, ?CarbonImmutable $now = null): Collection
    {
        $now ??= CarbonImmutable::now();

        return $this->all()
            ->filter(fn (Bar $bar) => $this->isLive($bar, $now) && $this->targeting->matches($bar, $page))
            ->values();
    }

    /**
     * Whether a bar is live anywhere: switched on, within its dates, and
     * with something to show (a Cargo code or free delivery threshold that
     * still applies).
     */
    public function isLive(Bar $bar, ?CarbonImmutable $now = null): bool
    {
        $now ??= CarbonImmutable::now();

        if (! $bar->enabled() || ! $bar->withinSchedule($now)) {
            return false;
        }

        if ($bar->get('code_source') === 'cargo' && $this->cargo->code($bar, $now) === null) {
            return false;
        }

        if ($bar->get('free_delivery') && $this->cargo->threshold($bar, $now) === null) {
            return false;
        }

        return true;
    }

    /**
     * Every bar live now on any page, highest priority first.
     *
     * @return Collection<int, Bar>
     */
    public function liveAnywhere(?CarbonImmutable $now = null): Collection
    {
        return $this->all()->filter(fn (Bar $bar) => $this->isLive($bar, $now))->values();
    }

    /**
     * The IDs of every bar live now, to tell when that changes.
     *
     * @return array<int, string>
     */
    public function liveIds(?CarbonImmutable $now = null): array
    {
        return $this->liveAnywhere($now)->map(fn (Bar $bar) => $bar->id())->all();
    }
}
