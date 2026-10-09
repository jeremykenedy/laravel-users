<?php

namespace jeremykenedy\laravelusers\Test\Feature;

use DOMDocument;
use DOMXPath;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Jeremykenedy\LaravelToast\Providers\ToastServiceProvider;
use jeremykenedy\laravelusers\Support\Frontend;
use jeremykenedy\laravelusers\Support\ManagedPackages;
use jeremykenedy\laravelusers\Support\PackageOperations;
use jeremykenedy\laravelusers\Support\PackageRequirements;
use jeremykenedy\laravelusers\Test\TestCase;

class PackageCompletionTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->directory = sys_get_temp_dir().'/laravelusers-package-completion-'.bin2hex(random_bytes(8));
        $this->app->setBasePath($this->directory);
        File::ensureDirectoryExists(config_path());
        File::put(base_path('composer.json'), json_encode(['autoload' => ['psr-4' => ['App\\' => 'app/']]]));
        config(['laravelusers.settings.enabled' => true, 'laravelusers.settings.packages.enabled' => true]);
        Gate::define('manage-laravelusers-settings', fn () => true);
        Gate::define('manage-laravelusers-packages', fn () => true);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->directory);
        parent::tearDown();
    }

    public function test_toast_setup_needs_the_installed_package_registered_views_and_a_published_file(): void
    {
        $this->requireToast();
        $packages = new ManagedPackages();
        $this->assertFalse($packages->toastSetupComplete());
        File::put(config_path('toast.php'), '<?php return [];');
        $this->assertFalse($packages->toastSetupComplete());
        $this->app->register(ToastServiceProvider::class);
        $this->assertTrue($packages->toastSetupComplete());

        File::delete(config_path('toast.php'));
        $this->assertFalse($packages->toastSetupComplete());
        File::ensureDirectoryExists(config_path('toast.php'));
        $this->assertFalse($packages->toastSetupComplete());
        File::deleteDirectory(config_path('toast.php'));
        File::put(config_path('toast.php'), '<?php return [];');
        $removed = \Mockery::mock(ManagedPackages::class)->makePartial();
        $removed->shouldReceive('installed')->with('toast')->andReturnFalse();
        $this->assertFalse($removed->toastSetupComplete());
    }

    public function test_completed_toast_setup_is_a_plain_check_sentence_and_preserves_removal(): void
    {
        $this->requireToast();
        $this->app->register(ToastServiceProvider::class);
        File::put(config_path('toast.php'), '<?php return [];');
        $this->requirements(true);
        $this->actingAs($this->user());

        foreach (Frontend::FRAMEWORKS as $framework) {
            config(['laravelusers.frontend' => $framework]);
            $document = $this->document($this->get('/users/settings')->assertOk()->getContent());
            $completed = $document->query('//p[@data-lu-package-setup-completed="toast"]');
            $this->assertSame(1, $completed->length, $framework);
            $this->assertSame('Setup completed.', trim($completed->item(0)->textContent));
            $this->assertSame(1, $document->query('//p[@data-lu-package-setup-completed="toast"]//*[@data-lu-icon="check"]')->length);
            $this->assertSame(0, $document->query('//p[@data-lu-package-setup-completed="toast"]//button | //button[@data-lu-package="toast" and @data-lu-package-operation="configure"]')->length);
            $this->assertSame(1, $document->query('//button[@data-lu-package="toast" and @data-lu-package-operation="remove" and not(@disabled)]')->length);
        }
    }

    public function test_cached_completion_stays_private_escaped_and_subject_to_current_authorization(): void
    {
        $this->requirements(true);
        $actor = $this->user();
        $id = (string) Str::uuid();
        $message = 'Completed <img src=x onerror="alert(1)">';
        PackageOperations::remember($id, ['actor' => (string) $actor->getKey(), 'status' => 'completed', 'stage' => 'completed', 'message' => $message, 'lock_owner' => 'private-owner', 'command' => '/private/host/composer']);
        $this->actingAs($actor);
        $status = $this->getJson('/users/settings/packages/'.$id)->assertOk()->assertJsonPath('status', 'completed')->assertJsonPath('message', $message);
        $this->assertStringContainsString('no-store', $status->headers->get('Cache-Control'));
        $this->assertArrayNotHasKey('lock_owner', $status->json());
        $this->assertArrayNotHasKey('command', $status->json());
        $document = $this->document($this->get('/users/settings')->assertOk()->getContent());
        $this->assertSame(0, $document->query('//*[@data-lu-package-operation-status]//img | //*[@data-lu-package-operation-status]//*[@onerror]')->length);
        $this->assertSame('Package change completed.', trim($document->query('//*[@data-lu-package-operation-status]')->item(0)->textContent));
        $this->assertSame(0, $document->query('//a[@data-lu-package-refresh]')->length);

        PackageOperations::remember($id, ['actor' => (string) $actor->getKey(), 'status' => 'failed', 'message' => $message]);
        $document = $this->document($this->get('/users/settings')->assertOk()->getContent());
        $this->assertSame(0, $document->query('//*[@data-lu-package-operation-status]//img | //*[@data-lu-package-operation-status]//*[@onerror]')->length);
        $this->assertStringContainsString($message, $document->query('//*[@data-lu-package-operation-status]')->item(0)->textContent);

        $this->actingAs($this->user())->getJson('/users/settings/packages/'.$id)->assertNotFound();
        $this->get('/users/settings')->assertOk()->assertDontSee($message)->assertDontSee($id);
        $this->actingAs($actor);
        Gate::define('manage-laravelusers-packages', fn () => false);
        $this->getJson('/users/settings/packages/'.$id)->assertForbidden();
        $this->get('/users/settings')->assertOk()->assertDontSee($id)->assertDontSee('id="lu-package-dialog"', false);
    }

    public function test_cached_success_cannot_enable_package_actions_when_queue_requirements_are_lost(): void
    {
        $this->requirements(false);
        config(['queue.default' => 'sync', 'queue.connections.sync.driver' => 'sync']);
        $actor = $this->user();
        PackageOperations::remember((string) Str::uuid(), ['actor' => (string) $actor->getKey(), 'status' => 'completed', 'message' => 'Previous package operation completed.']);
        $document = $this->document($this->actingAs($actor)->get('/users/settings')->assertOk()->getContent());
        $this->assertSame(1, $document->query('//*[@data-lu-package-status and @data-state="failed"]')->length);
        $this->assertSame(1, $document->query('//*[@data-lu-package-operation-status and @data-state="completed"]')->length);
        $this->assertSame(0, $document->query('//button[@data-lu-package and @data-lu-package!="requirements" and not(@disabled)]')->length);
        Bus::fake();
        $packages = \Mockery::mock(ManagedPackages::class)->makePartial();
        $packages->shouldReceive('installed')->andReturnFalse();
        $this->app->instance(ManagedPackages::class, $packages);
        $this->postJson('/users/settings/packages', ['package' => 'spatie', 'operation' => 'install', 'confirmation' => 'continue', 'acknowledgement' => 1])->assertUnprocessable()->assertJsonValidationErrors('package');
        Bus::assertNothingDispatched();
    }

    private function requirements(bool $ready): void
    {
        $this->mock(PackageRequirements::class)->shouldReceive('status')->andReturn(['status' => $ready ? 'completed' : 'not_ready', 'queue_ready' => $ready, 'message' => $ready ? 'Worker verified.' : 'Worker unavailable.']);
    }

    private function requireToast(): void
    {
        if (!class_exists(ToastServiceProvider::class)) {
            $this->markTestSkipped('Run the optional presentation integration job to test Laravel Toast.');
        }
    }

    private function document(string $html): DOMXPath
    {
        $previous = libxml_use_internal_errors(true);
        $document = new DOMDocument();
        $document->loadHTML($html);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        return new DOMXPath($document);
    }
}
