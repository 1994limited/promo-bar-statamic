<?php

namespace NineteenNinetyFour\PromoBar;

use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use Statamic\Facades\File;
use Statamic\Facades\Stache;
use Statamic\Facades\YAML;
use Throwable;

/**
 * One promo bar: its message and button, when and where it shows, and how
 * it looks. Its fields are the bar blueprint's (BarBlueprint), kept as
 * they're saved by the Control Panel.
 */
class Bar
{
    /** Fields kept outside data(). */
    protected const OWN = ['id', 'order', 'created_at', 'updated_at'];

    protected string $id;

    protected array $data = [];

    protected int $order = 0;

    protected ?CarbonImmutable $createdAt = null;

    protected ?CarbonImmutable $updatedAt = null;

    protected ?string $initialPath = null;

    /** As it was last read or saved, to tell what a save changed. */
    protected ?array $original = null;

    public static function make(array $data = []): static
    {
        $bar = new static;
        $bar->id = (string) Str::uuid();
        $bar->data = array_merge(static::defaults(), $data);

        return $bar;
    }

    public static function fromArray(string $id, array $data): static
    {
        $bar = new static;
        $bar->id = $id;
        $bar->order = (int) ($data['order'] ?? 0);
        $bar->createdAt = static::date($data['created_at'] ?? null);
        $bar->updatedAt = static::date($data['updated_at'] ?? null);
        $bar->data = array_merge(static::defaults(), array_diff_key($data, array_flip(static::OWN)));
        $bar->original = $bar->fileData();

        return $bar;
    }

    /**
     * The bar as it was when last read or saved, or null if it's new.
     */
    public function original(): ?static
    {
        return $this->original === null ? null : static::fromArray($this->id, $this->original);
    }

    public function syncOriginal(): static
    {
        $this->original = $this->fileData();

        return $this;
    }

    /**
     * What a new bar starts with.
     */
    public static function defaults(): array
    {
        return [
            'title' => null,
            'enabled' => true,
            'pages' => 'everywhere',
            'audience' => 'everyone',
            'dismissible' => true,
        ];
    }

    public static function date(mixed $value): ?CarbonImmutable
    {
        if ($value instanceof \DateTimeInterface) {
            return CarbonImmutable::instance($value);
        }

        if (! is_string($value) && ! is_int($value)) {
            return null;
        }

        try {
            return is_int($value)
                ? CarbonImmutable::createFromTimestamp($value, config('app.timezone'))
                : CarbonImmutable::parse($value, config('app.timezone'));
        } catch (Throwable) {
            return null;
        }
    }

    public function id(): string
    {
        return $this->id;
    }

    public function title(): string
    {
        $title = trim((string) ($this->data['title'] ?? ''));

        return $title !== '' ? $title : __('Untitled bar');
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $value = $this->data[$key] ?? null;

        return $value === null || $value === '' || $value === [] ? $default : $value;
    }

    public function set(string $key, mixed $value): static
    {
        $this->data[$key] = $value;

        return $this;
    }

    public function data(): array
    {
        return $this->data;
    }

    /**
     * Replace the bar's fields (as the Control Panel saves them).
     */
    public function merge(array $data): static
    {
        $this->data = array_merge($this->data, array_diff_key($data, array_flip(static::OWN)));

        return $this;
    }

    public function enabled(): bool
    {
        return (bool) ($this->data['enabled'] ?? false);
    }

    public function order(?int $order = null): static|int
    {
        if (func_num_args() === 0) {
            return $this->order;
        }

        $this->order = $order;

        return $this;
    }

    public function startsAt(): ?CarbonImmutable
    {
        return static::date($this->get('starts_at'));
    }

    public function endsAt(): ?CarbonImmutable
    {
        return static::date($this->get('ends_at'));
    }

    public function createdAt(): ?CarbonImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): ?CarbonImmutable
    {
        return $this->updatedAt;
    }

    public function touch(): static
    {
        $now = CarbonImmutable::now();
        $this->createdAt ??= $now;
        $this->updatedAt = $now;

        return $this;
    }

    /**
     * The sites it shows on. Empty means every site.
     *
     * @return array<int, string>
     */
    public function sites(): array
    {
        return array_values(array_filter((array) $this->get('sites', []), 'is_string'));
    }

    public function showsOnSite(string $site): bool
    {
        $sites = $this->sites();

        return $sites === [] || in_array($site, $sites, true);
    }

    /**
     * Within its dates, ignoring whether it's switched on.
     */
    public function withinSchedule(?CarbonImmutable $now = null): bool
    {
        $now ??= CarbonImmutable::now();
        $starts = $this->startsAt();
        $ends = $this->endsAt();

        return ($starts === null || $starts->lessThanOrEqualTo($now))
            && ($ends === null || $ends->greaterThan($now));
    }

    public function status(?CarbonImmutable $now = null): BarStatus
    {
        $now ??= CarbonImmutable::now();

        if (! $this->enabled()) {
            return BarStatus::Disabled;
        }

        if ($this->endsAt() && $this->endsAt()->lessThanOrEqualTo($now)) {
            return BarStatus::Expired;
        }

        if ($this->startsAt() && $this->startsAt()->greaterThan($now)) {
            return BarStatus::Scheduled;
        }

        return BarStatus::Live;
    }

    /**
     * Changes when its content changes, so a visitor who dismissed it sees
     * it again once it says something new.
     */
    public function contentHash(): string
    {
        $content = array_intersect_key($this->data, array_flip([
            'message', 'cta_label', 'cta_url', 'code_source', 'code', 'cargo_discount',
            'free_delivery', 'free_delivery_threshold', 'free_delivery_discount', 'ends_at',
        ]));

        return substr(md5(json_encode($content)), 0, 8);
    }

    /**
     * A copy with a new ID, named as a copy and switched off.
     */
    public function duplicate(): static
    {
        $copy = static::make($this->data);
        $copy->data['title'] = __(':title (copy)', ['title' => $this->title()]);
        $copy->data['enabled'] = false;

        return $copy;
    }

    /**
     * What a file holds, and the shape fromArray() reads.
     */
    public function fileData(): array
    {
        return array_filter([
            ...$this->data,
            'order' => $this->order,
            'created_at' => $this->createdAt?->toIso8601String(),
            'updated_at' => $this->updatedAt?->toIso8601String(),
        ], fn ($value) => $value !== null);
    }

    public function toArray(): array
    {
        return ['id' => $this->id] + $this->fileData();
    }

    /*
    |--------------------------------------------------------------------------
    | Flat files (file driver)
    |--------------------------------------------------------------------------
    */

    public function initialPath(?string $path = null): static|string|null
    {
        if (func_num_args() === 0) {
            return $this->initialPath;
        }

        $this->initialPath = $path;

        return $this;
    }

    public function path(): string
    {
        return rtrim(Stache::store('promo-bars')->directory(), '/').'/'.$this->id.'.yaml';
    }

    public function writeFile(): void
    {
        File::put($this->path(), YAML::dump($this->fileData()));
    }

    public function deleteFile(): void
    {
        File::delete($this->path());
    }
}
