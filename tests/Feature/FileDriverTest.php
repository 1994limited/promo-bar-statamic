<?php

namespace NineteenNinetyFour\PromoBar\Tests\Feature;

use NineteenNinetyFour\PromoBar\Tests\TestCase;
use Statamic\Facades\Stache;

class FileDriverTest extends TestCase
{
    use RepositoryContract;

    public function test_bars_are_yaml_files_named_by_id(): void
    {
        $bar = $this->bar(['title' => 'On disk']);

        $this->assertSame(Stache::store('promo-bars')->directory().$bar->id().'.yaml', str_replace('//', '/', $bar->path()));
    }
}
