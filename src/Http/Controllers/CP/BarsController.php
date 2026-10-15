<?php

namespace NineteenNinetyFour\PromoBar\Http\Controllers\CP;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use NineteenNinetyFour\PromoBar\Bar;
use NineteenNinetyFour\PromoBar\Blueprints\BarBlueprint;
use NineteenNinetyFour\PromoBar\PromoBars;
use NineteenNinetyFour\PromoBar\ServiceProvider;
use NineteenNinetyFour\PromoBar\Styles;
use NineteenNinetyFour\PromoBar\Targeting;
use Statamic\CP\PublishForm;
use Statamic\Exceptions\NotFoundHttpException;
use Statamic\Facades\Site;
use Statamic\Facades\User;
use Statamic\Http\Controllers\CP\CpController;

/**
 * Content → Promo Bars: every bar in priority order, and making, changing,
 * reordering and deleting them.
 */
class BarsController extends CpController
{
    /** Fields the form has that aren't kept. */
    protected const NOT_SAVED = ['preview', 'style_preview'];

    public function __construct(Request $request, protected PromoBars $bars, protected Styles $styles)
    {
        parent::__construct($request);
    }

    public function index(): Response
    {
        $this->authorize('view promo bars');

        $user = User::current();

        return Inertia::render('promo-bar::Index', [
            'bars' => $this->bars->all()->map(fn (Bar $bar) => $this->row($bar))->values()->all(),
            'canEdit' => $user->can('edit promo bars'),
            'multisite' => Site::multiEnabled(),
            'urls' => [
                'create' => cp_route('promo-bar.create'),
                'reorder' => cp_route('promo-bar.reorder'),
                'settings' => cp_route('addons.settings.edit', 'promo-bar-statamic'),
            ],
        ]);
    }

    public function create()
    {
        $this->authorize('edit promo bars');

        return PublishForm::make($this->blueprint())
            ->icon(ServiceProvider::ICON)
            ->title(__('New Promo Bar'))
            ->values(Bar::defaults() + ['code_source' => 'none'])
            ->submittingTo(cp_route('promo-bar.store'), 'POST');
    }

    public function store(Request $request): array
    {
        $this->authorize('edit promo bars');

        $bar = Bar::make($this->values($request));
        $this->bars->save($bar);

        session()->flash('success', __('Saved'));

        return ['redirect' => cp_route('promo-bar.edit', $bar->id())];
    }

    public function edit(string $id)
    {
        $this->authorize('view promo bars');

        $bar = $this->find($id);

        return PublishForm::make($this->blueprint())
            ->icon(ServiceProvider::ICON)
            ->title($bar->title())
            ->values($bar->data())
            ->readOnly(! User::current()->can('edit promo bars'))
            ->submittingTo(cp_route('promo-bar.update', $bar->id()));
    }

    public function update(Request $request, string $id): array
    {
        $this->authorize('edit promo bars');

        $bar = $this->find($id);
        $this->bars->save($bar->merge($this->values($request)));

        return ['saved' => true];
    }

    public function destroy(string $id): array
    {
        $this->authorize('edit promo bars');

        $this->bars->delete($this->find($id));

        return ['deleted' => true];
    }

    public function duplicate(string $id): array
    {
        $this->authorize('edit promo bars');

        $copy = $this->find($id)->duplicate();
        $this->bars->save($copy);

        return ['redirect' => cp_route('promo-bar.edit', $copy->id())];
    }

    public function toggle(string $id): array
    {
        $this->authorize('edit promo bars');

        $bar = $this->find($id);
        $this->bars->save($bar->set('enabled', ! $bar->enabled()));

        return ['bar' => $this->row($bar)];
    }

    public function reorder(Request $request): array
    {
        $this->authorize('edit promo bars');

        $ids = $request->validate(['ids' => ['required', 'array', 'max:500'], 'ids.*' => ['string']])['ids'];
        $this->bars->reorder($ids);

        return ['bars' => $this->bars->all()->map(fn (Bar $bar) => $this->row($bar))->values()->all()];
    }

    /**
     * The form's values, checked by the blueprint and then for what a field
     * can't check on its own.
     */
    protected function values(Request $request): array
    {
        $values = PublishForm::make($this->blueprint())->submit($request->all());
        $values = collect($values)->except(self::NOT_SAVED)->all();

        $starts = Bar::date($values['starts_at'] ?? null);
        $ends = Bar::date($values['ends_at'] ?? null);

        if ($starts && $ends && $ends->lessThanOrEqualTo($starts)) {
            throw ValidationException::withMessages(['ends_at' => __('The end must be after the start.')]);
        }

        if (! empty($values['countdown']) && ! $ends) {
            throw ValidationException::withMessages(['ends_at' => __('A countdown needs an end date.')]);
        }

        if (($values['pages'] ?? 'everywhere') !== 'everywhere' && empty($values['entries']) && empty($values['collections']) && blank($values['urls'] ?? null)) {
            throw ValidationException::withMessages(['urls' => __('Choose some entries, collections or URLs.')]);
        }

        return $values;
    }

    protected function blueprint()
    {
        return app(BarBlueprint::class)->make();
    }

    protected function find(string $id): Bar
    {
        return $this->bars->find($id) ?? throw new NotFoundHttpException;
    }

    protected function row(Bar $bar): array
    {
        $status = $bar->status();
        $style = $this->styles->for($bar);

        return [
            'id' => $bar->id(),
            'title' => $bar->title(),
            'message' => $this->plain($bar->get('message')),
            'status' => $status->value,
            'status_label' => $status->label(),
            'enabled' => $bar->enabled(),
            'starts' => $bar->startsAt()?->format('j M Y, H:i'),
            'ends' => $bar->endsAt()?->format('j M Y, H:i'),
            'where' => $this->where($bar),
            'sites' => $bar->sites() ? collect($bar->sites())->map(fn ($handle) => Site::get($handle)?->name() ?? $handle)->implode(', ') : __('All sites'),
            'style' => Styles::labels()[$style] ?? $style,
            'edit_url' => cp_route('promo-bar.edit', $bar->id()),
            'delete_url' => cp_route('promo-bar.destroy', $bar->id()),
            'duplicate_url' => cp_route('promo-bar.duplicate', $bar->id()),
            'toggle_url' => cp_route('promo-bar.toggle', $bar->id()),
        ];
    }

    protected function where(Bar $bar): string
    {
        $pages = $bar->get('pages', 'everywhere');
        $count = count(Targeting::entries($bar)) + count(Targeting::collections($bar)) + count(Targeting::patterns($bar));

        $where = match ($pages) {
            'only' => trans_choice('Only on :count page rule|Only on :count page rules', $count),
            'except' => trans_choice('Everywhere except :count page rule|Everywhere except :count page rules', $count),
            default => __('Everywhere'),
        };

        return match ($bar->get('audience')) {
            'guests' => $where.' · '.__('guests'),
            'signed_in' => $where.' · '.__('signed in'),
            default => $where,
        };
    }

    /**
     * A Bard value as plain text, for the list.
     */
    protected function plain(mixed $value): string
    {
        if (is_string($value)) {
            return $value;
        }

        $text = '';

        array_walk_recursive($value, function ($item, $key) use (&$text) {
            if ($key === 'text' && is_string($item)) {
                $text .= $item;
            }
        });

        return (string) str($text)->squish()->limit(120);
    }
}
