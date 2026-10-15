<?php

namespace NineteenNinetyFour\PromoBar\Tests\Feature;

/**
 * What every storage driver must do, run against each.
 */
trait RepositoryContract
{
    public function test_saving_and_finding(): void
    {
        $bar = $this->bar(['title' => 'Saved', 'sites' => ['default'], 'ends_at' => '2030-01-01 00:00']);

        $found = $this->bars()->find($bar->id());

        $this->assertSame('Saved', $found->title());
        $this->assertSame(['default'], $found->sites());
        $this->assertSame($bar->get('message'), $found->get('message'));
        $this->assertNotNull($found->createdAt());
    }

    public function test_all_in_order(): void
    {
        $a = $this->bar(['title' => 'A']);
        $b = $this->bar(['title' => 'B']);

        $this->assertSame(['A', 'B'], $this->bars()->all()->map->title()->all());

        $this->bars()->reorder([$b->id(), $a->id()]);

        $this->assertSame(['B', 'A'], $this->bars()->all()->map->title()->all());
    }

    public function test_deleting(): void
    {
        $bar = $this->bar();
        $this->bars()->delete($bar);

        $this->assertNull($this->bars()->find($bar->id()));
        $this->assertCount(0, $this->bars()->all());
    }
}
