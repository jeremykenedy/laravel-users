<?php

namespace jeremykenedy\laravelusers\Test\Feature;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use jeremykenedy\laravelusers\Support\ImpersonationSession;
use jeremykenedy\laravelusers\Support\UserActivity;
use jeremykenedy\laravelusers\Test\TestCase;

class ImpersonationStateTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Route::get('/host-dashboard', fn () => 'Host dashboard')->middleware('web');
    }

    public function test_ordinary_host_requests_are_unchanged_when_impersonation_is_disabled(): void
    {
        $actor = $this->user();
        $this->get('/host-dashboard')->assertOk()->assertSee('Host dashboard');
        $this->actingAs($actor)->get('/host-dashboard')->assertOk();
        $this->assertAuthenticatedAs($actor);
    }

    private function begin(): array
    {
        $actor = $this->user();
        $target = $this->user();
        $this->actingAs($actor);
        $request = Request::create('/users/'.$target->id.'/impersonate', 'POST', server: ['HTTP_REFERER' => 'http://localhost/users?page=2']);
        $request->setLaravelSession($this->app['session']->driver());
        $this->app->instance('request', $request);
        $this->app->make(ImpersonationSession::class)->begin($request, $actor, $target);

        return [$actor, $target, $request->session()->get(ImpersonationSession::KEY)];
    }

    public function test_state_is_encrypted_and_bound_to_the_actor_target_and_guard(): void
    {
        [$actor, $target, $state] = $this->begin();
        $this->assertSame((string) $actor->id, $state['actor_id']);
        $this->assertSame((string) $target->id, $state['target_id']);
        $this->assertSame('web', $state['guard']);
        $this->assertSame(3600, $state['expires_at'] - $state['started_at']);
        $this->assertNotEmpty($state['proof']);
        $this->assertStringNotContainsString('actor_id', $state['proof']);
        $this->withSession([ImpersonationSession::KEY => $state])->post('/users/impersonation/stop')->assertRedirect('/users?page=2');
        $this->assertAuthenticatedAs($actor);
        $this->assertFalse(session()->has(ImpersonationSession::KEY));
    }

    public function test_modified_state_cannot_execute_host_routes_or_restore_a_different_actor(): void
    {
        [, $target, $state] = $this->begin();
        $state['actor_id'] = (string) $target->id;
        $this->withSession([ImpersonationSession::KEY => $state])->get('/host-dashboard')->assertForbidden()->assertDontSee('Host dashboard');
        $this->assertGuest();
        $this->assertFalse(session()->has(ImpersonationSession::KEY));
    }

    public function test_malformed_or_legacy_state_fails_closed(): void
    {
        foreach ([['actor_id' => 1, 'guard' => 'web'], 'invalid', ['proof' => 'invalid']] as $state) {
            $this->actingAs($this->user())->withSession([ImpersonationSession::KEY => $state])->get('/host-dashboard')->assertForbidden();
            $this->assertGuest();
        }
    }

    public function test_expired_or_revoked_access_restores_the_actor_before_the_host_request_runs(): void
    {
        [$actor, , $state] = $this->begin();
        $this->withSession([ImpersonationSession::KEY => $state])->get('/host-dashboard')->assertRedirect('/users?page=2')->assertSessionHas('warning');
        $this->assertAuthenticatedAs($actor);
        $this->assertFalse(session()->has(ImpersonationSession::KEY));
    }

    public function test_expiry_can_be_configured_and_stop_remains_available_after_expiration(): void
    {
        config(['laravelusers.impersonation.timeout' => 5]);
        [$actor, $target, $state] = $this->begin();
        $this->assertSame(300, $state['expires_at'] - $state['started_at']);
        $this->travel(6)->minutes();
        $this->withSession([ImpersonationSession::KEY => $state])->post('/users/impersonation/stop')->assertRedirect('/users?page=2');
        $this->assertAuthenticatedAs($actor);
    }

    public function test_deleted_actors_end_the_session(): void
    {
        [$actor, $target, $state] = $this->begin();
        $actor->delete();
        $this->withSession([ImpersonationSession::KEY => $state])->get('/host-dashboard')->assertForbidden();
        $this->assertGuest();
    }

    public function test_changed_target_identity_ends_the_session(): void
    {
        [$actor, $target, $state] = $this->begin();
        $this->actingAs($actor)->withSession([ImpersonationSession::KEY => $state])->get('/host-dashboard')->assertForbidden();
        $this->assertGuest();
    }

    public function test_external_return_urls_cannot_redirect_to_another_host(): void
    {
        $actor = $this->user();
        $target = $this->user();
        $this->actingAs($actor);
        $request = Request::create('/users/'.$target->id.'/impersonate', 'POST', server: ['HTTP_REFERER' => 'https://another.example//another.example/path']);
        $request->setLaravelSession($this->app['session']->driver());
        $this->app->make(ImpersonationSession::class)->begin($request, $actor, $target);
        $state = $request->session()->get(ImpersonationSession::KEY);
        $this->withSession([ImpersonationSession::KEY => $state])->post('/users/impersonation/stop')->assertRedirect('/users');
    }

    public function test_begin_and_restore_do_not_record_login_activity(): void
    {
        (require dirname(__DIR__, 2).'/src/database/migrations/2026_10_07_000000_create_laravelusers_login_activity_table.php')->up();
        config(['laravelusers.activity.login' => true, 'laravelusers.activity.online' => true]);
        [$actor, $target, $state] = $this->begin();
        $this->assertNull($this->app->make(UserActivity::class)->lastLogin($target));
        $this->withSession([ImpersonationSession::KEY => $state])->post('/users/impersonation/stop')->assertRedirect('/users?page=2');
        $this->assertNull($this->app->make(UserActivity::class)->lastLogin($target));
        $this->assertNull($this->app->make(UserActivity::class)->lastLogin($actor));
    }
}
