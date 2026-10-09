<?php

namespace jeremykenedy\laravelusers\Test\Feature;

use Illuminate\Queue\WorkerOptions;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use jeremykenedy\laravelusers\Support\ComposerPackages;
use jeremykenedy\laravelusers\Support\ManagedPackages;
use jeremykenedy\laravelusers\Support\PackageOperations;
use jeremykenedy\laravelusers\Support\PackageRequirements;
use jeremykenedy\laravelusers\Support\PackageWorker;
use jeremykenedy\laravelusers\Test\TestCase;

/**
 * Integration fixtures exercise the framework types and optional providers used by this feature.
 *
 * @SuppressWarnings("PHPMD.CouplingBetweenObjects")
 */
class PackageRequirementsTest extends TestCase
{
    private string $directory;

    private ComposerPackages $composer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->directory = sys_get_temp_dir().'/laravelusers-requirements-'.bin2hex(random_bytes(8));
        $this->app->setBasePath($this->directory);
        $this->app->useStoragePath($this->directory.'/storage');
        File::ensureDirectoryExists(storage_path('framework/views'));
        File::put(base_path('composer.json'), json_encode(['autoload' => ['psr-4' => ['App\\' => 'app/']]]));
        $this->composer = \Mockery::mock(ComposerPackages::class);
        $this->composer->shouldReceive('readiness')->andReturnNull()->byDefault();
        $this->app->instance(ComposerPackages::class, $this->composer);
    }

    protected function tearDown(): void
    {
        $this->travelBack();
        File::deleteDirectory($this->directory);
        parent::tearDown();
    }

    public function test_cli_setup_creates_an_isolated_queue_without_changing_host_defaults(): void
    {
        $queue = config('queue.default');
        $cache = config('cache.default');
        foreach (['laravelusers:install', 'laravelusers:update'] as $command) {
            $this->artisan($command, ['--setup-packages' => true, '--no-interaction' => true])->assertExitCode(0);
            $this->assertTrue(Schema::hasTable('laravelusers_package_jobs'));
            $this->assertSame($queue, config('queue.default'));
            $this->assertSame($cache, config('cache.default'));
            $this->assertSame(600, config('queue.connections.laravelusers-packages.retry_after'));
            $this->assertTrue($this->app->make(ManagedPackages::class)->queueReady());
            $this->assertFalse(PackageWorker::verified());
            $this->assertSame(0, DB::table('laravelusers_package_jobs')->count());
        }
        $this->assertFileExists(config_path('laravelusers-packages.php'));
        $this->assertDirectoryDoesNotExist(database_path('migrations'));
        $migration = require dirname(__DIR__, 2).'/src/database/package-jobs/2026_10_08_181116_create_laravelusers_package_jobs_table.php';
        $migration->down();
        $this->assertFalse(Schema::hasTable('laravelusers_package_jobs'));
    }

    public function test_gui_setup_requires_explicit_confirmation_and_authorization(): void
    {
        $this->withoutMiddleware(ThrottleRequests::class);
        $this->postJson('/users/settings/packages', $this->setupPayload())->assertUnauthorized();
        $this->actingAs($this->user())->postJson('/users/settings/packages', $this->setupPayload())->assertForbidden();
        $this->enable();
        $this->postJson('/users/settings/packages', array_replace($this->setupPayload(), ['confirmation' => 'wrong']))->assertUnprocessable();
        $this->postJson('/users/settings/packages', array_replace($this->setupPayload(), ['acknowledgement' => 0]))->assertUnprocessable();
        $this->assertFalse(Schema::hasTable('laravelusers_package_jobs'));
        $this->postJson('/users/settings/packages', $this->setupPayload())->assertOk()->assertJsonPath('status', 'checking')->assertJsonPath('queue_ready', false);
        $this->assertTrue(Schema::hasTable('laravelusers_package_jobs'));
        $this->assertSame(1, DB::table('laravelusers_package_jobs')->count());
        $this->actingAs($this->user());
        $this->postJson('/users/settings/packages', $this->setupPayload())->assertForbidden();
        $this->postJson('/users/settings/packages/verify', $this->verifyPayload())->assertForbidden();
        $this->assertSame(1, DB::table('laravelusers_package_jobs')->count());
    }

    public function test_http_verification_requires_the_worker_round_trip_and_a_fresh_lease(): void
    {
        $this->enable();
        $this->actingAs($this->user());
        $this->postJson('/users/settings/packages', $this->setupPayload())
            ->assertOk()->assertJsonPath('status', 'checking')->assertJsonPath('queue_ready', false)
            ->assertHeader('Cache-Control', 'no-store, private');
        $this->postJson('/users/settings/packages/verify', $this->verifyPayload())
            ->assertOk()->assertJsonPath('status', 'checking')->assertJsonPath('queue_ready', false);
        $this->assertSame(1, DB::table('laravelusers_package_jobs')->count());
        $this->assertFalse(PackageWorker::verified());

        $this->work();

        $this->postJson('/users/settings/packages/verify', $this->verifyPayload())
            ->assertOk()->assertJsonPath('status', 'completed')->assertJsonPath('queue_ready', true)
            ->assertHeader('Cache-Control', 'no-store, private');
        $this->assertSame(0, DB::table('laravelusers_package_jobs')->count());
        $this->travel(61)->seconds();
        $this->postJson('/users/settings/packages/verify', $this->verifyPayload())
            ->assertOk()->assertJsonPath('status', 'checking')->assertJsonPath('queue_ready', false);
        $this->assertSame(1, DB::table('laravelusers_package_jobs')->count());
    }

    public function test_gui_setup_returns_the_new_worker_connection_and_queue_before_verification(): void
    {
        $this->enable();
        config(['queue.default' => 'database', 'laravelusers.settings.packages.queue' => 'managed-packages']);
        $command = 'php artisan queue:work laravelusers-packages --queue=managed-packages --timeout=360';

        $this->actingAs($this->user())->postJson('/users/settings/packages', $this->setupPayload())
            ->assertOk()->assertJsonPath('queue_ready', false)->assertJsonPath('worker_command', $command);
        $this->assertSame('database', config('queue.default'));
        $this->assertSame('managed-packages', DB::table('laravelusers_package_jobs')->value('queue'));
        $this->postJson('/users/settings/packages/verify', $this->verifyPayload())
            ->assertOk()->assertJsonPath('queue_ready', false)->assertJsonPath('worker_command', $command);
        foreach (['bootstrap4', 'bootstrap5'] as $framework) {
            config(['laravelusers.frontend' => $framework]);
            $this->get('/users/settings')->assertOk()->assertSee('<code>'.$command.'</code>', false);
        }
        $this->work();
        $this->postJson('/users/settings/packages/verify', $this->verifyPayload())
            ->assertOk()->assertJsonPath('queue_ready', true)->assertJsonPath('worker_command', $command);
    }

    public function test_all_css_frameworks_render_verified_state_only_after_worker_confirmation(): void
    {
        $this->enable();
        $this->actingAs($this->user())->postJson('/users/settings/packages', $this->setupPayload())->assertOk();
        foreach (['bootstrap4', 'bootstrap5', 'tailwind', 'materialize', 'material3', 'bulma', 'foundation'] as $framework) {
            config(['laravelusers.frontend' => $framework]);
            $this->get('/users/settings')->assertOk()
                ->assertSee('<span data-lu-package-verify-label>Verify package requirements</span>', false)
                ->assertDontSee('<span data-lu-package-verify-label>Re-Verify package requirements</span>', false);
        }
        $this->work();
        foreach (['bootstrap4', 'bootstrap5', 'tailwind', 'materialize', 'material3', 'bulma', 'foundation'] as $framework) {
            config(['laravelusers.frontend' => $framework]);
            $this->get('/users/settings')->assertOk()
                ->assertSee('<span data-lu-package-status-message>'.trans('laravelusers::ui.package_requirements_verified').'</span>', false)
                ->assertSee('<span data-lu-package-verify-label>Re-Verify package requirements</span>', false)
                ->assertSee('data-lu-package-status-verified', false);
        }
        Schema::drop('laravelusers_package_jobs');
        $this->get('/users/settings')->assertOk()
            ->assertSee('<span data-lu-package-verify-label>Verify package requirements</span>', false)
            ->assertDontSee('<span data-lu-package-verify-label>Re-Verify package requirements</span>', false);
    }

    public function test_worker_composer_failure_is_reported_without_exposing_internal_state(): void
    {
        $this->enable();
        $this->composer->shouldReceive('readiness')->times(3)->andReturn(null, 'laravelusers::ui.package_composer_vendor', null);
        $this->actingAs($this->user())->postJson('/users/settings/packages', $this->setupPayload())
            ->assertOk()->assertJsonPath('status', 'checking');
        $this->work();

        $response = $this->postJson('/users/settings/packages/verify', $this->verifyPayload())
            ->assertOk()->assertJsonPath('status', 'not_ready')->assertJsonPath('queue_ready', false)
            ->assertJsonPath('message', trans('laravelusers::ui.package_composer_vendor'));

        $this->assertEqualsCanonicalizing(['status', 'queue_ready', 'message', 'worker_command'], array_keys($response->json()));
        $this->assertFalse(PackageWorker::verified());
        $this->assertSame(0, DB::table('laravelusers_package_jobs')->count());
    }

    public function test_verified_requirements_remain_visible_alongside_package_operation_results(): void
    {
        $this->enable();
        $actor = $this->user();
        $this->actingAs($actor)->postJson('/users/settings/packages', $this->setupPayload())->assertOk();
        $this->work();

        foreach (['queued', 'completed', 'failed'] as $state) {
            $message = 'Package operation '.$state.'.';
            PackageOperations::remember((string) Str::uuid(), ['actor' => (string) $actor->getKey(), 'status' => $state, 'message' => $message]);
            foreach (['bootstrap4', 'bootstrap5', 'tailwind', 'materialize', 'material3', 'bulma', 'foundation'] as $framework) {
                config(['laravelusers.frontend' => $framework]);
                $response = $this->get('/users/settings')->assertOk()
                    ->assertSee('<span data-lu-package-status-message>'.trans('laravelusers::ui.package_requirements_verified').'</span>', false)
                    ->assertSee('<span data-lu-package-status-message>'.($state === 'completed' ? trans('laravelusers::ui.package_change_completed') : $message), false)
                    ->assertSee('data-lu-package-operation-status data-state="'.$state.'"', false)
                    ->assertSee('<span data-lu-package-verify-label>Re-Verify package requirements</span>', false);
                if ($state === 'completed') {
                    $response->assertDontSee('data-lu-package-refresh>', false);
                }
            }
        }
    }

    public function test_host_composer_failure_prevents_worker_probe_dispatch(): void
    {
        $this->enable();
        $this->composer->shouldReceive('readiness')->once()->andReturn('laravelusers::ui.package_composer_manifest');

        $this->actingAs($this->user())->postJson('/users/settings/packages', $this->setupPayload())
            ->assertOk()->assertJsonPath('status', 'not_ready')->assertJsonPath('queue_ready', false)
            ->assertJsonPath('message', trans('laravelusers::ui.package_composer_manifest'));

        $this->assertSame(0, DB::table('laravelusers_package_jobs')->count());
        $this->assertFalse(PackageWorker::verified());
    }

    public function test_existing_setup_configuration_is_preserved(): void
    {
        File::ensureDirectoryExists(config_path());
        $contents = "<?php return ['connection' => 'redis', 'cache' => 'file', 'database' => null];\n";
        File::put(config_path('laravelusers-packages.php'), $contents);
        $this->app->make(PackageRequirements::class)->configure();
        $this->assertSame($contents, File::get(config_path('laravelusers-packages.php')));
        $this->assertSame('redis', config('laravelusers.settings.packages.connection'));
        $this->assertSame('file', config('laravelusers.settings.packages.cache'));
    }

    private function enable(): void
    {
        config(['laravelusers.settings.enabled' => true, 'laravelusers.settings.packages.enabled' => true]);
        Gate::define('manage-laravelusers-settings', fn ($user) => $user->id === 1);
        Gate::define('manage-laravelusers-packages', fn ($user) => $user->id === 1);
    }

    private function setupPayload(): array
    {
        return ['package' => 'requirements', 'operation' => 'setup', 'confirmation' => 'continue', 'acknowledgement' => 1];
    }

    private function verifyPayload(): array
    {
        return ['package' => 'requirements', 'operation' => 'verify'];
    }

    private function work(): void
    {
        $options = new WorkerOptions();
        $options->sleep = 0;
        $this->app->make('queue.worker')->runNextJob(config('laravelusers.settings.packages.connection'), config('laravelusers.settings.packages.queue', 'default'), $options);
    }
}
