<?php

namespace NineteenNinetyFour\PromoBar\Tests\Feature;

use Mockery;
use NineteenNinetyFour\PromoBar\Tests\TestCase;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Statamic\Facades\Site;
use Statamic\StaticCaching\Cacher;

class MultisiteTest extends TestCase
{
    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('statamic.system.multisite', true);
    }

    protected function setUp(): void
    {
        parent::setUp();

        Site::setSites([
            'en' => ['name' => 'English', 'url' => 'http://localhost/', 'locale' => 'en_GB'],
            'fr' => ['name' => 'French', 'url' => 'http://localhost/fr/', 'locale' => 'fr_FR'],
        ]);

        Collection::find('pages')->sites(['en', 'fr'])->save();

        $about = Entry::make()->collection('pages')->locale('en')->id('about')->slug('about')->data(['title' => 'About']);
        $about->save();
        Entry::make()->collection('pages')->locale('fr')->origin('about')->id('a-propos')->slug('a-propos')->data(['title' => 'À propos'])->save();
    }

    public function test_a_bar_for_an_entry_shows_on_its_translations(): void
    {
        $this->bar(['pages' => 'only', 'entries' => ['about']], 'About bar');

        $this->assertStringContainsString('About bar', $this->antlers('{{ promo_bar }}', ['id' => 'a-propos']));
    }

    public function test_listing_a_translation_lists_the_original_too(): void
    {
        $this->bar(['pages' => 'only', 'entries' => ['a-propos']], 'About bar');

        $this->assertStringContainsString('About bar', $this->antlers('{{ promo_bar }}', ['id' => 'about']));
    }

    public function test_a_bar_for_one_site_clears_only_that_sites_pages(): void
    {
        $this->staticCaching();
        $cacher = Mockery::spy(Cacher::class);
        $this->app->instance(Cacher::class, $cacher);

        $this->bar(['sites' => ['fr'], 'pages' => 'only', 'entries' => ['about']]);

        $cacher->shouldHaveReceived('invalidateUrls')->with(['http://localhost/fr/a-propos'])->once();
    }

    public function test_a_bar_for_one_site(): void
    {
        $this->bar(['sites' => ['fr']], 'Bonjour');

        Site::setCurrent('en');
        $this->assertStringNotContainsString('Bonjour', $this->antlers('{{ promo_bar }}'));

        Site::setCurrent('fr');
        $this->assertStringContainsString('Bonjour', $this->antlers('{{ promo_bar }}'));
    }

    public function test_saving_clears_the_entry_in_every_site(): void
    {
        $this->staticCaching();
        $cacher = Mockery::spy(Cacher::class);
        $this->app->instance(Cacher::class, $cacher);

        $this->bar(['pages' => 'only', 'entries' => ['a-propos']]);

        $cacher->shouldHaveReceived('invalidateUrls')->with(['http://localhost/about', 'http://localhost/fr/a-propos'])->once();
    }
}
