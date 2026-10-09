<?php

namespace jeremykenedy\laravelusers\Test\Feature;

use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use jeremykenedy\laravelusers\Jobs\ChangeManagedPackage;
use jeremykenedy\laravelusers\Support\ComposerPackages;
use jeremykenedy\laravelusers\Support\ManagedPackages;
use jeremykenedy\laravelusers\Support\UserSettings;
use jeremykenedy\laravelusers\Test\TestCase;

class ManagedPackagesTest extends TestCase
{
    private ?string $cachePath = null;

    protected function tearDown(): void
    {
        if ($this->cachePath) {
            File::deleteDirectory($this->cachePath);
        }
        parent::tearDown();
    }

    private function enable(array $installed = []): ManagedPackages
    {
        config(['laravelusers.settings.enabled' => true, 'laravelusers.settings.packages.enabled' => true, 'queue.default' => 'database', 'queue.connections.database.driver' => 'database', 'queue.connections.database.retry_after' => 600]);
        $this->cachePath = sys_get_temp_dir().'/laravelusers-package-tests-'.bin2hex(random_bytes(8));
        config(['cache.default' => 'file', 'cache.stores.file.driver' => 'file', 'cache.stores.file.path' => $this->cachePath, 'cache.stores.file.lock_path' => $this->cachePath.'/locks']);
        Cache::forgetDriver('file');
        Gate::define('manage-laravelusers-settings', fn ($user) => $user->id === 1);
        Gate::define('manage-laravelusers-packages', fn ($user) => $user->id === 1);
        Bus::fake();
        $packages = \Mockery::mock(ManagedPackages::class)->makePartial();
        $packages->shouldReceive('installed')->andReturnUsing(fn ($package) => in_array($package, $installed, true));
        $this->app->instance(ManagedPackages::class, $packages);

        return $packages;
    }

    private function payload(string $package = 'spatie', string $operation = 'install'): array
    {
        return ['package' => $package, 'operation' => $operation, 'confirmation' => $operation === 'remove' ? 'remove' : 'continue', 'acknowledgement' => 1];
    }

    public function test_package_management_requires_both_settings_access_and_its_own_gate(): void
    {
        $actor = $this->user();
        $this->actingAs($actor)->postJson('/users/settings/packages', $this->payload())->assertForbidden();
        $this->enable();
        Gate::define('manage-laravelusers-packages', fn () => false);
        $this->postJson('/users/settings/packages', $this->payload())->assertForbidden();
        $this->get('/users/settings')->assertOk()->assertDontSee('id="lu-package-dialog"', false);
        Gate::define('manage-laravelusers-packages', fn () => true);
        $this->actingAs($this->user())->postJson('/users/settings/packages', $this->payload())->assertForbidden();
        Bus::assertNothingDispatched();
    }

    public function test_confirmation_and_package_allowlist_are_checked_on_the_server(): void
    {
        $this->enable();
        $this->withoutMiddleware(ThrottleRequests::class);
        $this->actingAs($this->user());
        foreach ([['confirmation' => 'Continue'], ['acknowledgement' => 0], ['package' => 'other/package'], ['operation' => 'update']] as $changes) {
            $this->postJson('/users/settings/packages', array_replace($this->payload(), $changes))->assertUnprocessable();
        }
        Bus::assertNothingDispatched();
    }

    public function test_removal_requires_its_own_typed_confirmation_and_acknowledgement(): void
    {
        $this->enable(['toast']);
        $this->actingAs($this->user());
        $this->postJson('/users/settings/packages', array_replace($this->payload('toast', 'remove'), ['confirmation' => 'continue']))->assertUnprocessable()->assertJsonValidationErrors('confirmation');
        $this->postJson('/users/settings/packages', array_replace($this->payload('toast', 'remove'), ['acknowledgement' => 0]))->assertUnprocessable()->assertJsonValidationErrors('acknowledgement');
        Bus::assertNothingDispatched();
        $this->postJson('/users/settings/packages', $this->payload('toast', 'remove'))->assertStatus(202);
        Bus::assertDispatchedTimes(ChangeManagedPackage::class, 1);
    }

    public function test_second_roles_package_and_unsafe_removal_are_blocked(): void
    {
        $packages = $this->enable(['laravel-roles']);
        $this->actingAs($this->user())->postJson('/users/settings/packages', $this->payload('spatie'))->assertUnprocessable()->assertJsonValidationErrors('package');
        config(['laravelusers.rolesEnabled' => true]);

        try {
            $packages->check('laravel-roles', 'remove');
            $this->fail('A connected roles package must not be removed.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString('still uses', $exception->errors()['package'][0]);
        }
        config(['laravelusers.rolesEnabled' => false, 'laravelusers.access.view_users.mode' => 'restricted']);
        $this->postJson('/users/settings/packages', $this->payload('laravel-roles', 'remove'))->assertUnprocessable();
        Bus::assertNothingDispatched();
    }

    public function test_queue_requirements_pending_lock_and_owner_scoped_status(): void
    {
        $this->enable();
        $this->actingAs($this->user());
        config(['queue.connections.database.retry_after' => 90]);
        $this->postJson('/users/settings/packages', $this->payload())->assertUnprocessable();
        config(['queue.connections.database.retry_after' => 600]);
        $response = $this->postJson('/users/settings/packages', $this->payload())->assertStatus(202);
        $url = $response->json('status_url');
        $status = $this->getJson($url)->assertOk()->assertJsonPath('status', 'queued');
        $this->assertArrayNotHasKey('actor', $status->json());
        $this->postJson('/users/settings/packages', $this->payload())->assertUnprocessable();
        Bus::assertDispatchedTimes(ChangeManagedPackage::class, 1);
        $this->actingAs($this->user());
        Gate::define('manage-laravelusers-settings', fn () => true);
        Gate::define('manage-laravelusers-packages', fn () => true);
        $this->getJson($url)->assertNotFound();
    }

    public function test_package_cards_explain_missing_queue_requirements_and_disable_install_actions(): void
    {
        $this->enable();
        config(['queue.default' => 'sync', 'queue.connections.sync.driver' => 'sync']);
        $response = $this->actingAs($this->user())->get('/users/settings');

        $response->assertOk()
            ->assertSee('data-lu-package-requirements', false)
            ->assertSee('Installing or removing packages from this page needs a persistent queue, a running worker and a shared cache.', false);
        $this->assertMatchesRegularExpression('/<button[^>]*data-lu-package="toast"[^>]*disabled/', $response->getContent());
        $this->assertMatchesRegularExpression('/<button[^>]*data-lu-package="laravel-roles"[^>]*disabled/', $response->getContent());
        $response->assertSee('(Preferred)', false);
    }

    public function test_worker_rechecks_authorization_before_running_composer(): void
    {
        $packages = $this->enable();
        $this->actingAs($this->user())->postJson('/users/settings/packages', $this->payload())->assertStatus(202);
        $job = Bus::dispatched(ChangeManagedPackage::class)->first();
        $composer = \Mockery::mock(ComposerPackages::class);
        $composer->shouldNotReceive('changeFromSettings');
        Gate::define('manage-laravelusers-packages', fn () => false);
        $job->handle($packages, $composer, new UserSettings());
        $this->assertSame('failed', Cache::get('laravelusers.package.'.$job->id)['status']);
        $lock = Cache::lock('laravelusers.packages', 10);
        $this->assertTrue($lock->get());
        $lock->release();
    }

    public function test_worker_reports_completion_and_a_duplicate_job_cannot_change_dependencies_again(): void
    {
        $packages = $this->enable();
        $this->actingAs($this->user())->postJson('/users/settings/packages', $this->payload())->assertStatus(202);
        $job = Bus::dispatched(ChangeManagedPackage::class)->first();
        $composer = \Mockery::mock(ComposerPackages::class);
        $composer->shouldReceive('changeFromSettings')->once()->with('install', 'spatie/laravel-permission')->andReturnTrue();
        $job->handle($packages, $composer, new UserSettings());
        $this->assertSame('completed', Cache::get('laravelusers.package.'.$job->id)['status']);
        $job->handle($packages, $composer, new UserSettings());
        $this->assertSame('completed', Cache::get('laravelusers.package.'.$job->id)['status']);
    }

    public function test_worker_only_runs_migrations_when_the_installation_request_includes_them(): void
    {
        $packages = $this->enable();
        $this->actingAs($this->user());
        foreach ([false, true] as $migrate) {
            $this->postJson('/users/settings/packages', $this->payload() + ['setup' => true, 'migrate' => $migrate])->assertStatus(202);
            $job = Bus::dispatched(ChangeManagedPackage::class)->last();
            $composer = \Mockery::mock(ComposerPackages::class);
            $composer->shouldReceive('changeFromSettings')->once()->with('install', 'spatie/laravel-permission')->andReturnTrue();
            $composer->shouldReceive('setup')->once()->with('spatie', 'bootstrap4', $migrate, \Mockery::type('callable'))->andReturnTrue();
            $job->handle($packages, $composer, new UserSettings());
            $this->assertSame('completed', Cache::get('laravelusers.package.'.$job->id)['status']);
        }
    }

    public function test_toast_installation_always_runs_setup_with_the_current_framework(): void
    {
        $packages = $this->enable();
        config(['laravelusers.frontend' => 'bootstrap5']);
        $this->actingAs($this->user());
        if (PHP_VERSION_ID < 80200 || version_compare($this->app->version(), '10.0.0', '<')) {
            $this->postJson('/users/settings/packages', $this->payload('toast') + ['setup' => false])
                ->assertUnprocessable()
                ->assertJsonPath('errors.package.0', 'Laravel Toast requires PHP 8.2 or newer and Laravel 10 or newer.');
            Bus::assertNothingDispatched();

            return;
        }
        foreach ([[], ['setup' => false, 'migrate' => true]] as $options) {
            $response = $this->postJson('/users/settings/packages', $this->payload('toast') + $options)->assertStatus(202);
            $job = Bus::dispatched(ChangeManagedPackage::class)->last();
            $composer = \Mockery::mock(ComposerPackages::class);
            $composer->shouldReceive('changeFromSettings')->once()->with('install', 'jeremykenedy/laravel-toast')->andReturnTrue();
            $composer->shouldReceive('setup')->once()->with('toast', 'bootstrap5', false, \Mockery::type('callable'))->andReturnTrue();
            $job->handle($packages, $composer, new UserSettings());
            $this->getJson($response->json('status_url'))->assertOk()->assertJsonPath('status', 'completed')->assertJsonPath('stage', 'completed');
        }
    }

    public function test_installed_package_setup_runs_without_reinstalling_and_keeps_server_confirmations(): void
    {
        $packages = $this->enable(['toast']);
        $this->withoutMiddleware(ThrottleRequests::class);
        $this->actingAs($this->user());
        $payload = $this->payload('toast', 'configure');
        $this->postJson('/users/settings/packages', array_replace($payload, ['confirmation' => 'Continue']))->assertUnprocessable()->assertJsonValidationErrors('confirmation');
        $this->postJson('/users/settings/packages', array_replace($payload, ['acknowledgement' => false]))->assertUnprocessable()->assertJsonValidationErrors('acknowledgement');
        $this->postJson('/users/settings/packages', $this->payload('spatie', 'configure'))->assertUnprocessable()->assertJsonValidationErrors('package');
        Bus::assertNothingDispatched();

        $response = $this->postJson('/users/settings/packages', $payload)->assertStatus(202);
        $job = Bus::dispatched(ChangeManagedPackage::class)->last();
        $composer = \Mockery::mock(ComposerPackages::class);
        $composer->shouldNotReceive('changeFromSettings');
        $composer->shouldReceive('setup')->once()->with('toast', 'bootstrap4', false, \Mockery::type('callable'))->andReturnTrue();
        $job->handle($packages, $composer, new UserSettings());
        $this->getJson($response->json('status_url'))->assertOk()->assertJsonPath('status', 'completed')->assertJsonPath('message', 'Package setup completed.');
        $this->get('/users/settings')->assertOk()->assertSee('data-lu-package-operation="configure"', false)->assertDontSee('php artisan toast:install', false);
    }

    public function test_setup_failure_is_visible_and_can_be_retried_without_composer_changes(): void
    {
        $packages = $this->enable(['toast']);
        $this->actingAs($this->user());
        foreach ([false, true] as $success) {
            $response = $this->postJson('/users/settings/packages', $this->payload('toast', 'configure'))->assertStatus(202);
            $job = Bus::dispatched(ChangeManagedPackage::class)->last();
            $composer = \Mockery::mock(ComposerPackages::class);
            $composer->shouldNotReceive('changeFromSettings');
            $composer->shouldReceive('setup')->once()->andReturn($success);
            $job->handle($packages, $composer, new UserSettings());
            $status = $this->getJson($response->json('status_url'))->assertOk()->assertJsonPath('status', $success ? 'completed' : 'failed');
            if (!$success) {
                $status->assertJsonPath('stage', 'setup')->assertJsonPath('message', 'Package setup failed. Review the application logs, then use Complete setup in settings before enabling the integration.');
            }
        }
    }

    public function test_composer_and_setup_failures_are_reported_without_leaving_the_package_lock_held(): void
    {
        $packages = $this->enable();
        $this->actingAs($this->user());
        foreach ([false, true] as $installed) {
            $this->postJson('/users/settings/packages', $this->payload() + ['setup' => true])->assertStatus(202);
            $job = Bus::dispatched(ChangeManagedPackage::class)->last();
            $composer = \Mockery::mock(ComposerPackages::class);
            $composer->shouldReceive('changeFromSettings')->once()->andReturn($installed);
            if ($installed) {
                $composer->shouldReceive('setup')->once()->andReturnFalse();
            } else {
                $composer->shouldNotReceive('setup');
            }
            $job->handle($packages, $composer, new UserSettings());
            $this->assertSame('failed', Cache::get('laravelusers.package.'.$job->id)['status']);
            $lock = Cache::lock('laravelusers.packages', 10);
            $this->assertTrue($lock->get());
            $lock->release();
        }
    }

    public function test_a_worker_cannot_run_while_another_composer_process_holds_the_execution_lock(): void
    {
        $packages = $this->enable();
        $this->actingAs($this->user())->postJson('/users/settings/packages', $this->payload())->assertStatus(202);
        $job = Bus::dispatched(ChangeManagedPackage::class)->first();
        $composer = \Mockery::mock(ComposerPackages::class);
        $composer->shouldNotReceive('changeFromSettings');
        $execution = Cache::lock('laravelusers.composer', 30);
        $this->assertTrue($execution->get());
        $job->handle($packages, $composer, new UserSettings());
        $this->assertSame('failed', Cache::get('laravelusers.package.'.$job->id)['status']);
        $this->assertFalse(Cache::lock('laravelusers.composer', 10)->get());
        $execution->release();
    }

    public function test_an_expired_job_cannot_release_a_newer_package_operation_lock(): void
    {
        $packages = $this->enable();
        $this->actingAs($this->user())->postJson('/users/settings/packages', $this->payload())->assertStatus(202);
        $job = Bus::dispatched(ChangeManagedPackage::class)->first();
        Cache::restoreLock('laravelusers.packages', $job->lockOwner)->release();
        $newer = Cache::lock('laravelusers.packages', 30);
        $this->assertTrue($newer->get());
        Cache::put('laravelusers.packages.owner', $newer->owner());
        $composer = \Mockery::mock(ComposerPackages::class);
        $composer->shouldNotReceive('changeFromSettings');
        $job->handle($packages, $composer, new UserSettings());
        $this->assertSame('failed', Cache::get('laravelusers.package.'.$job->id)['status']);
        $this->assertSame($newer->owner(), Cache::get('laravelusers.packages.owner'));
        $this->assertFalse(Cache::lock('laravelusers.packages', 10)->get());
        $newer->release();
    }
}
