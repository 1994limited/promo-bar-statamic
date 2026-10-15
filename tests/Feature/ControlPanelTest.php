<?php

namespace NineteenNinetyFour\PromoBar\Tests\Feature;

use Inertia\Testing\AssertableInertia;
use NineteenNinetyFour\PromoBar\Tests\TestCase;
use Statamic\Facades\User;

class ControlPanelTest extends TestCase
{
    protected function editor()
    {
        return $this->user('editor@example.com', ['view promo bars', 'edit promo bars']);
    }

    protected function values(array $values = []): array
    {
        return [
            'title' => 'Autumn sale',
            'enabled' => true,
            'message' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Up to 30% off']]]],
            'pages' => 'everywhere',
            'audience' => 'everyone',
            'code_source' => 'none',
            'dismissible' => true,
            ...$values,
        ];
    }

    public function test_the_list_needs_permission(): void
    {
        $this->actingAs($this->user('nobody@example.com', ['access cp']))->getJson(cp_route('promo-bar.index'))->assertForbidden();
    }

    public function test_the_list_in_priority_order(): void
    {
        $this->bar(['title' => 'First']);
        $this->bar(['title' => 'Second', 'enabled' => false]);

        $this->actingAs($this->editor())
            ->get(cp_route('promo-bar.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('promo-bar::Index')
                ->where('bars.0.title', 'First')
                ->where('bars.0.status', 'live')
                ->where('bars.0.message', 'Free delivery this weekend')
                ->where('bars.1.status', 'disabled')
                ->where('canEdit', true));
    }

    public function test_viewers_cant_change_anything(): void
    {
        $bar = $this->bar();
        $viewer = $this->user('viewer@example.com', ['view promo bars']);

        $this->actingAs($viewer)->getJson(cp_route('promo-bar.create'))->assertForbidden();
        $this->actingAs($viewer)->patchJson(cp_route('promo-bar.update', $bar->id()), $this->values())->assertForbidden();
        $this->actingAs($viewer)->deleteJson(cp_route('promo-bar.destroy', $bar->id()))->assertForbidden();
        $this->actingAs($viewer)->postJson(cp_route('promo-bar.reorder'), ['ids' => [$bar->id()]])->assertForbidden();
    }

    public function test_the_create_form(): void
    {
        $this->actingAs($this->editor())
            ->get(cp_route('promo-bar.create'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('PublishForm')
                ->where('submitMethod', 'POST')
                ->where('values.enabled', true));
    }

    public function test_creating_a_bar(): void
    {
        $response = $this->actingAs($this->editor())
            ->postJson(cp_route('promo-bar.store'), $this->values(['cta_label' => 'Shop', 'cta_url' => '/shop', 'preview' => 'ignored']))
            ->assertOk();

        $bar = $this->bars()->all()->first();

        $this->assertSame('Autumn sale', $bar->title());
        $this->assertSame('/shop', $bar->get('cta_url'));
        $this->assertArrayNotHasKey('preview', $bar->data());
        $this->assertSame(cp_route('promo-bar.edit', $bar->id()), $response->json('redirect'));
    }

    public function test_a_name_and_message_are_needed(): void
    {
        $this->actingAs($this->editor())
            ->postJson(cp_route('promo-bar.store'), $this->values(['title' => '', 'message' => null]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['title', 'message']);
    }

    public function test_the_end_must_be_after_the_start(): void
    {
        $this->actingAs($this->editor())
            ->postJson(cp_route('promo-bar.store'), $this->values([
                'starts_at' => '2026-10-20T08:00:00.000Z',
                'ends_at' => '2026-10-19T08:00:00.000Z',
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('ends_at');
    }

    public function test_a_countdown_needs_an_end(): void
    {
        $this->actingAs($this->editor())
            ->postJson(cp_route('promo-bar.store'), $this->values(['countdown' => true]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('ends_at');
    }

    public function test_only_on_needs_something_to_show_on(): void
    {
        $this->actingAs($this->editor())
            ->postJson(cp_route('promo-bar.store'), $this->values(['pages' => 'only']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('urls');
    }

    public function test_a_typed_code_is_needed_when_chosen(): void
    {
        $this->actingAs($this->editor())
            ->postJson(cp_route('promo-bar.store'), $this->values(['code_source' => 'text']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('code');
    }

    public function test_dates_are_saved_in_the_apps_timezone(): void
    {
        $this->actingAs($this->editor())
            ->postJson(cp_route('promo-bar.store'), $this->values([
                'starts_at' => '2026-10-20T08:00:00.000Z',
                'ends_at' => '2026-10-27T23:59:00.000Z',
            ]))
            ->assertOk();

        $bar = $this->bars()->all()->first();

        $this->assertSame('2026-10-20T09:00:00+01:00', $bar->startsAt()->toIso8601String());
    }

    public function test_editing_a_bar(): void
    {
        $bar = $this->bar();

        $this->actingAs($this->editor())
            ->get(cp_route('promo-bar.edit', $bar->id()))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('PublishForm')->where('title', 'Weekend')->where('values.title', 'Weekend'));

        $this->actingAs($this->editor())
            ->patchJson(cp_route('promo-bar.update', $bar->id()), $this->values(['title' => 'Renamed']))
            ->assertOk();

        $this->assertSame('Renamed', $this->bars()->find($bar->id())->title());
    }

    public function test_a_missing_bar_is_not_found(): void
    {
        $this->actingAs($this->editor())->get(cp_route('promo-bar.edit', 'nope'))->assertNotFound();
    }

    public function test_duplicating_toggling_and_deleting(): void
    {
        $bar = $this->bar();

        $this->actingAs($this->editor())->postJson(cp_route('promo-bar.duplicate', $bar->id()))->assertOk();
        $copy = $this->bars()->all()->last();
        $this->assertSame('Weekend (copy)', $copy->title());
        $this->assertFalse($copy->enabled());

        $this->actingAs($this->editor())->postJson(cp_route('promo-bar.toggle', $bar->id()))->assertOk()->assertJsonPath('bar.enabled', false);
        $this->assertFalse($this->bars()->find($bar->id())->enabled());

        $this->actingAs($this->editor())->deleteJson(cp_route('promo-bar.destroy', $bar->id()))->assertOk();
        $this->assertNull($this->bars()->find($bar->id()));
    }

    public function test_reordering(): void
    {
        $a = $this->bar(['title' => 'A']);
        $b = $this->bar(['title' => 'B']);
        $c = $this->bar(['title' => 'C']);

        $this->actingAs($this->editor())
            ->postJson(cp_route('promo-bar.reorder'), ['ids' => [$c->id(), $a->id()]])
            ->assertOk()
            ->assertJsonPath('bars.0.title', 'C')
            ->assertJsonPath('bars.2.title', 'B');

        $this->assertSame(['C', 'A', 'B'], $this->bars()->all()->map->title()->all());
    }

    public function test_the_settings_page(): void
    {
        $this->actingAs($this->user('super@example.com')->makeSuper()->save() ?? User::findByEmail('super@example.com'))
            ->get(cp_route('addons.settings.edit', 'promo-bar-statamic'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('PublishForm')
                ->has('blueprint.tabs', 4)
                ->where('values.style_solid.background', '#1d4ed8'));
    }
}
