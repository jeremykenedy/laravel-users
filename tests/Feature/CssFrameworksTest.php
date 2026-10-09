<?php

namespace jeremykenedy\laravelusers\Test\Feature;

use DOMDocument;
use DOMXPath;
use Illuminate\Filesystem\Filesystem;
use jeremykenedy\laravelusers\Support\PublicAssets;
use jeremykenedy\laravelusers\Test\TestCase;

class CssFrameworksTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->directory = sys_get_temp_dir().'/laravelusers-css-'.bin2hex(random_bytes(8));
        $this->app->getNamespace();
        $this->app->setBasePath($this->directory);
        $this->app->useStoragePath($this->directory.'/storage');
    }

    protected function tearDown(): void
    {
        (new Filesystem())->deleteDirectory($this->directory);
        parent::tearDown();
    }

    public function test_optional_framework_assets_are_local_and_only_the_selected_presentation_is_loaded(): void
    {
        $this->actingAs($this->user());
        (new PublicAssets(new Filesystem()))->publish();

        foreach (['materialize', 'material3', 'bulma', 'foundation'] as $framework) {
            config(['laravelusers.frontend' => $framework]);
            $response = $this->get('/users')->assertOk()
                ->assertSee(PublicAssets::url($framework.'.css'), false)
                ->assertDontSee('cdnjs.cloudflare.com', false)
                ->assertDontSee('cdn.jsdelivr.net', false)
                ->assertDontSee('fonts.googleapis.com', false);

            foreach (array_diff(['materialize', 'material3', 'bulma', 'foundation'], [$framework]) as $other) {
                $response->assertDontSee(PublicAssets::url($other.'.css'), false);
            }

            if ($framework === 'material3') {
                $document = new DOMDocument();
                $document->loadHTML($response->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING);
                $scripts = (new DOMXPath($document))->query('//script[@src="'.PublicAssets::url('material3.js').'"]');
                $this->assertCount(1, $scripts);
                $this->assertSame('module', $scripts->item(0)->getAttribute('type'));
                $this->assertTrue($scripts->item(0)->hasAttribute('data-navigate-once'));
            } else {
                $response->assertDontSee(PublicAssets::url('material3.js'), false);
            }
        }
    }

    public function test_default_and_existing_modern_frameworks_do_not_load_optional_presentation_assets(): void
    {
        $this->actingAs($this->user());
        (new PublicAssets(new Filesystem()))->publish();

        foreach (['bootstrap4', 'bootstrap5', 'tailwind'] as $framework) {
            config(['laravelusers.frontend' => $framework]);
            $response = $this->get('/users')->assertOk();
            foreach (['materialize.css', 'material3.css', 'bulma.css', 'foundation.css', 'material3.js'] as $asset) {
                $response->assertDontSee(PublicAssets::url($asset), false);
            }
        }
    }
}
