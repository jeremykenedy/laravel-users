<?php

namespace jeremykenedy\laravelusers\Test\Feature;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use jeremykenedy\laravelusers\Support\NativeRuntime;
use jeremykenedy\laravelusers\Test\Fixtures\User;
use jeremykenedy\laravelusers\Test\TestCase;
use Livewire\Livewire;
use Livewire\LivewireServiceProvider;

class NativeHttpSecurityTest extends TestCase
{
    private string $csrf = 'native-security-session-token';

    protected function getPackageProviders($app)
    {
        return array_merge(parent::getPackageProviders($app), class_exists(LivewireServiceProvider::class) ? [LivewireServiceProvider::class] : []);
    }

    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);
        $app['router']->aliasMiddleware('native-security-access', NativeSecurityRestriction::class);
        $app['router']->middlewareGroup('native-security-inner', ['native-security-access:native-security-directory']);
        $app['router']->middlewareGroup('native-security-outer', ['native-security-inner']);
        $app['config']->set('laravelusers.middleware', ['native-security-outer']);
    }

    protected function setUp(): void
    {
        parent::setUp();
        if (!class_exists(NativeRuntime::class)) {
            $this->markTestSkipped('This regression requires the native runtime integration.');
        }
        $this->app->instance('env', 'local');
        $this->withSession(['_token' => $this->csrf]);
        Gate::define('native-security-directory', fn () => true);
    }

    public function test_native_json_create_and_update_keep_server_validation(): void
    {
        $this->actingAs($this->user());
        $target = $this->user();
        $original = $target->fresh()->getAttributes();
        foreach (['livewire', 'vue', 'react', 'svelte'] as $runtime) {
            $headers = $this->headers($runtime);
            $this->postJson('/users', ['name' => '<script>invalid</script>', 'email' => 'invalid', 'password' => 'x', 'password_confirmation' => 'different'], $headers)
                ->assertUnprocessable()->assertJsonValidationErrors(['name', 'email', 'password']);
            $this->putJson('/users/'.$target->id, ['name' => '<script>invalid</script>', 'email' => 'invalid', 'password' => 'x', 'password_confirmation' => 'different'], $headers)
                ->assertUnprocessable()->assertJsonValidationErrors(['name', 'email', 'password']);
        }

        $this->assertSame(2, User::count());
        $this->assertSame($original, $target->fresh()->getAttributes());
    }

    public function test_native_json_writes_require_authentication_and_a_valid_csrf_token(): void
    {
        $headers = $this->headers('vue');
        $data = ['name' => 'NativeAccount', 'email' => 'native@example.com', 'password' => 'password', 'password_confirmation' => 'password'];
        $this->postJson('/users', $data, $headers)->assertUnauthorized();
        $this->actingAs($this->user());
        unset($headers['X-CSRF-TOKEN']);

        $this->postJson('/users', $data, $headers)->assertStatus(419);

        $this->assertSame(1, User::count());
    }

    public function test_native_headers_cannot_bypass_denied_write_permissions(): void
    {
        $this->actingAs($this->user());
        $target = $this->user();
        config(['laravelusers.settings.enabled' => true, 'laravelusers.access.create_users.mode' => 'deny', 'laravelusers.access.edit_users.mode' => 'deny', 'laravelusers.access.delete_users.mode' => 'deny']);
        foreach (['livewire', 'vue', 'react', 'svelte'] as $runtime) {
            $headers = $this->headers($runtime);
            $this->postJson('/users', ['name' => 'DeniedAccount', 'email' => 'denied@example.com', 'password' => 'password', 'password_confirmation' => 'password'], $headers)->assertForbidden();
            $this->putJson('/users/'.$target->id, ['name' => 'DeniedEdit', 'email' => $target->email], $headers)->assertForbidden();
            $this->deleteJson('/users/'.$target->id, [], $headers)->assertForbidden();
        }

        $this->assertSame(2, User::count());
        $this->assertSame($target->name, $target->fresh()->name);
    }

    public function test_native_user_writes_do_not_assign_unvalidated_attributes(): void
    {
        $this->actingAs($this->user());
        $headers = $this->headers('react');
        $this->postJson('/users', ['name' => 'NativeAccount', 'email' => 'native@example.com', 'password' => 'password', 'password_confirmation' => 'password', 'id' => 999, 'remember_token' => 'attacker-token', 'created_at' => '2000-01-01 00:00:00'], $headers)->assertOk();
        $target = User::where('email', 'native@example.com')->firstOrFail();
        $this->assertNotSame(999, $target->id);
        $this->assertNull($target->remember_token);
        $this->assertNotSame('2000-01-01 00:00:00', $target->getRawOriginal('created_at'));
        $password = $target->password;
        $created = $target->getRawOriginal('created_at');

        $this->putJson('/users/'.$target->id, ['name' => 'NativeUpdated', 'email' => $target->email, 'id' => 999, 'remember_token' => 'attacker-token', 'created_at' => '2000-01-01 00:00:00'], $headers)->assertOk();

        $this->assertSame('NativeUpdated', $target->fresh()->name);
        $this->assertNull($target->fresh()->remember_token);
        $this->assertSame($password, $target->fresh()->password);
        $this->assertSame($created, $target->fresh()->getRawOriginal('created_at'));
    }

    public function test_custom_views_remain_in_control_with_native_headers(): void
    {
        $this->actingAs($this->user());
        $headers = $this->headers('svelte');
        config(['laravelusers.frontend' => 'tailwind', 'laravelusers.showUsersBlade' => 'custom']);
        $this->get('/users', $headers)->assertOk()->assertSee('Custom users view')->assertDontSee('lu-native-page')->assertViewIs('custom');
    }

    public function test_published_views_remain_in_control_with_native_headers(): void
    {
        $this->actingAs($this->user());
        $headers = $this->headers('svelte');
        config(['laravelusers.frontend' => 'tailwind']);
        View::prependNamespace('laravelusers', __DIR__.'/../Fixtures/overrides');

        $this->get('/users', $headers)->assertOk()->assertSee('Published modern override')->assertDontSee('lu-native-page');
    }

    public function test_livewire_http_updates_require_csrf_and_recheck_configured_middleware(): void
    {
        $snapshot = $this->livewireSnapshot();
        $payload = $this->updatePayload($snapshot);
        $url = Livewire::getUpdateUri();
        $this->postJson($url, $payload, ['X-Livewire' => 'true'])->assertStatus(419);
        Livewire::flushState();
        $headers = ['X-Livewire' => 'true', 'X-CSRF-TOKEN' => $this->csrf];
        $this->postJson($url, $payload, $headers)->assertOk();
        Livewire::flushState();
        Gate::define('native-security-directory', fn () => false);

        $this->postJson($url, $payload, $headers)->assertForbidden();
    }

    public function test_livewire_http_updates_recheck_package_access_after_revocation(): void
    {
        config(['laravelusers.settings.enabled' => true]);
        $snapshot = $this->livewireSnapshot();
        config(['laravelusers.access.view_users.mode' => 'deny']);
        Livewire::flushState();

        $this->postJson(Livewire::getUpdateUri(), $this->updatePayload($snapshot), ['X-Livewire' => 'true', 'X-CSRF-TOKEN' => $this->csrf])->assertForbidden();
    }

    private function headers(string $runtime): array
    {
        config(['laravelusers.runtime' => $runtime, 'laravelusers-ui.runtime' => $runtime]);

        return ['Accept' => 'application/json', 'X-LaravelUsers-Runtime' => $runtime, 'X-CSRF-TOKEN' => $this->csrf];
    }

    private function livewireSnapshot(): string
    {
        if (!class_exists(LivewireServiceProvider::class)) {
            $this->markTestSkipped('This regression requires the optional Livewire integration.');
        }
        config(['laravelusers.runtime' => 'livewire', 'laravelusers-ui.runtime' => 'livewire']);
        $html = $this->actingAs($this->user())->get('/users')->assertOk()->getContent();
        preg_match_all('/wire:snapshot="([^"]+)"/', $html, $matches);
        foreach ($matches[1] as $value) {
            $snapshot = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            if ((json_decode($snapshot, true)['memo']['name'] ?? null) === 'laravelusers.users-screen') {
                return $snapshot;
            }
        }
        $this->fail('The native screen did not return a signed Livewire snapshot.');
    }

    private function updatePayload(string $snapshot): array
    {
        return ['components' => [['snapshot' => $snapshot, 'updates' => ['search' => 'person'], 'calls' => [['path' => '', 'method' => 'searchUsers', 'params' => []]]]]];
    }
}

class NativeSecurityRestriction
{
    public function handle(Request $request, Closure $next, string $ability): mixed
    {
        Gate::forUser($request->user())->authorize($ability);

        return $next($request);
    }
}
