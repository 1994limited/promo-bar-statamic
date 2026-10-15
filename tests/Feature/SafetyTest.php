<?php

namespace NineteenNinetyFour\PromoBar\Tests\Feature;

use NineteenNinetyFour\PromoBar\Tests\TestCase;

/**
 * Bars that aren't live (switched off, scheduled, ended) can't be read
 * anywhere on the site before their time.
 */
class SafetyTest extends TestCase
{
    public function test_scheduled_bars_cant_be_read_from_the_page_the_stylesheet_or_the_state_endpoint(): void
    {
        $later = $this->bar([
            'title' => 'Black Friday',
            'starts_at' => now()->addMonth()->format('Y-m-d H:i'),
            'code_source' => 'text',
            'code' => 'SECRET50',
            'custom_colours' => true,
            'colour_background' => '#000000',
        ], 'Half price everything');
        $off = $this->bar(['title' => 'Draft', 'enabled' => false], 'Not ready');
        $this->bar(['title' => 'Now'], 'On now');

        foreach ([
            $this->antlers('{{ promo_bar mode="stack" }}'),
            $this->get('/!/promo-bar/promo-bar.css')->getContent(),
            $this->getJson('/!/promo-bar/state?bars='.$later->id().','.$off->id())->getContent(),
        ] as $output) {
            $this->assertStringNotContainsString('SECRET50', $output);
            $this->assertStringNotContainsString('Half price', $output);
            $this->assertStringNotContainsString('Black Friday', $output);
            $this->assertStringNotContainsString('Not ready', $output);
            $this->assertStringNotContainsString('data-promo-bar-id="'.$later->id(), $output);
        }
    }

    public function test_the_bar_files_are_not_served(): void
    {
        $bar = $this->bar();

        $this->get('/content/promo-bars/'.$bar->id().'.yaml')->assertNotFound();
    }
}
