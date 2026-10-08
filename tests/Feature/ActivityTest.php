<?php

namespace jeremykenedy\laravelusers\Test\Feature;

use Illuminate\Auth\Events\Authenticated;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Cache\CacheManager;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use jeremykenedy\laravelusers\Support\UserActivity;
use jeremykenedy\laravelusers\Test\Fixtures\Account;
use jeremykenedy\laravelusers\Test\TestCase;
use RuntimeException;

class ActivityTest extends TestCase
{
    private const AGENT = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Safari/537.36';

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        Request::setTrustedProxies([], 0);
        parent::tearDown();
    }

    private function migrate(): void
    {
        (require dirname(__DIR__, 2).'/src/database/migrations/2026_10_07_000000_create_laravelusers_login_activity_table.php')->up();
    }

    private function request(): Request
    {
        $request = Request::create('/login', 'POST', [], [], [], ['REMOTE_ADDR' => '192.0.2.10', 'HTTP_USER_AGENT' => self::AGENT]);
        $session = new Store('activity', new ArraySessionHandler(120));
        $session->start();
        $request->setLaravelSession($session);
        $this->app->instance('request', $request);

        return $request;
    }

    public function test_tracking_is_disabled_without_schema_changes_or_queries(): void
    {
        $user = $this->user();
        $queries = [];
        DB::listen(function ($query) use (&$queries) {
            $queries[] = $query->sql;
        });
        Event::dispatch(new Login('web', $user, false));
        Event::dispatch(new Authenticated('web', $user));
        $this->assertSame([], $queries);
        $this->assertNull($this->app->make(UserActivity::class)->lastLogin($user));
        $this->assertNull($this->app->make(UserActivity::class)->isOnline($user));
        $this->assertFalse(Schema::hasTable('laravelusers_login_activity'));
    }

    public function test_real_login_records_latest_details_and_authentication_does_not_replace_them(): void
    {
        $this->migrate();
        config(['laravelusers.activity.login' => true]);
        $user = $this->user();
        Route::middleware('web')->get('/activity-login', function () use ($user) {
            Auth::login($user);

            return 'Signed in';
        });
        Carbon::setTestNow('2026-10-07 12:00:00');
        $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.10'])->withHeaders(['User-Agent' => self::AGENT])->get('/activity-login')->assertOk();
        $activity = $this->app->make(UserActivity::class);
        $login = $activity->lastLogin($user);
        $this->assertSame('192.0.2.10', $login->ip_address);
        $this->assertNotSame('192.0.2.10', DB::table('laravelusers_login_activity')->value('ip_address'));
        $this->assertSame('Desktop', $login->device);
        $this->assertSame('Windows 10', $login->os);
        $this->assertSame('Chrome 130.0.0', $login->browser);
        $this->assertSame('2026-10-07 12:00:00', $login->last_login_at->toDateTimeString());
        Carbon::setTestNow('2026-10-07 13:00:00');
        Event::dispatch(new Authenticated('web', $user));
        $this->assertSame('2026-10-07 12:00:00', $activity->lastLogin($user)->last_login_at->toDateTimeString());
        Event::dispatch(new Login('web', $user, true));
        $this->assertSame('2026-10-07 13:00:00', $activity->lastLogin($user)->last_login_at->toDateTimeString());
        $this->assertSame(1, DB::table('laravelusers_login_activity')->count());
    }

    public function test_online_status_survives_session_regeneration_and_logout_only_removes_one_device(): void
    {
        config(['laravelusers.activity.online' => true]);
        $user = $this->user();
        $activity = $this->app->make(UserActivity::class);
        $first = $this->request();
        Event::dispatch(new Login('web', $user, false));
        $first->session()->regenerate();
        $second = $this->request();
        Event::dispatch(new Authenticated('web', $user));
        $this->app->instance('request', $first);
        Event::dispatch(new Logout('web', $user));
        $this->assertTrue($activity->isOnline($user));
        $this->app->instance('request', $second);
        Event::dispatch(new Logout('web', $user));
        $this->assertFalse($activity->isOnline($user));
        $this->assertFalse(Schema::hasTable('laravelusers_login_activity'));
    }

    public function test_online_window_expires_and_is_refreshed_by_authenticated_requests(): void
    {
        config(['laravelusers.activity.online' => true, 'laravelusers.activity.online_seconds' => 30]);
        Carbon::setTestNow('2026-10-07 12:00:00');
        $user = $this->user();
        $this->request();
        $activity = $this->app->make(UserActivity::class);
        Event::dispatch(new Authenticated('web', $user));
        Carbon::setTestNow('2026-10-07 12:00:20');
        $this->assertTrue($activity->isOnline($user));
        Event::dispatch(new Authenticated('web', $user));
        Carbon::setTestNow('2026-10-07 12:00:40');
        $this->assertTrue($activity->isOnline($user));
        Carbon::setTestNow('2026-10-07 12:00:50');
        $this->assertFalse($activity->isOnline($user));
    }

    public function test_other_guards_and_models_are_not_tracked(): void
    {
        $this->migrate();
        config(['laravelusers.activity.login' => true, 'laravelusers.activity.online' => true]);
        $user = $this->user();
        $this->request();
        Event::dispatch(new Login('admin', $user, false));
        Event::dispatch(new Login('web', new Account(['id' => $user->id]), false));
        $this->assertSame(0, DB::table('laravelusers_login_activity')->count());
        $this->assertFalse($this->app->make(UserActivity::class)->isOnline($user));
    }

    public function test_missing_table_and_cache_failures_do_not_prevent_login(): void
    {
        config(['laravelusers.activity.login' => true, 'laravelusers.activity.online' => true]);
        $user = $this->user();
        $this->request();
        $this->mock(ExceptionHandler::class, function ($mock) {
            $mock->shouldReceive('report')->twice();
        });
        $this->mock(CacheManager::class, function ($mock) {
            $mock->shouldReceive('store')->once()->andThrow(new RuntimeException('Cache unavailable'));
        });
        Event::dispatch(new Login('web', $user, false));
        $this->assertFalse(Schema::hasTable('laravelusers_login_activity'));
    }

    public function test_ip_uses_laravel_trusted_proxies_and_unknown_agents_are_supported(): void
    {
        $this->migrate();
        config(['laravelusers.activity.login' => true]);
        $user = $this->user();
        $request = $this->request();
        $request->headers->set('X-Forwarded-For', '198.51.100.20');
        $request->headers->set('User-Agent', '');
        $activity = $this->app->make(UserActivity::class);
        Event::dispatch(new Login('web', $user, false));
        $this->assertSame('192.0.2.10', $activity->lastLogin($user)->ip_address);
        $this->assertSame('Other', $activity->lastLogin($user)->browser);
        Request::setTrustedProxies(['192.0.2.10'], Request::HEADER_X_FORWARDED_FOR);
        Event::dispatch(new Login('web', $user, false));
        $this->assertSame('198.51.100.20', $activity->lastLogin($user)->ip_address);
    }

    public function test_string_identifiers_and_model_connections_do_not_collide(): void
    {
        $this->migrate();
        config(['laravelusers.activity.login' => true]);
        $user = $this->user();
        $user->id = '6ce9b1a8-2a1a-4b59-bd53-3f5c427050b4';
        $request = $this->request();
        $activity = $this->app->make(UserActivity::class);
        $activity->recordLogin($user, $request);
        $this->assertNotNull($activity->lastLogin($user));
        $other = clone $user;
        $other->setConnection('another');
        $this->assertNull($activity->lastLogin($other));
        $this->assertSame(1, DB::table('laravelusers_login_activity')->count());
    }

    public function test_deleting_a_user_removes_login_details_and_presence(): void
    {
        $this->migrate();
        config(['laravelusers.activity.login' => true, 'laravelusers.activity.online' => true]);
        $user = $this->user();
        $this->request();
        Event::dispatch(new Login('web', $user, false));
        $user->delete();
        $activity = $this->app->make(UserActivity::class);
        $this->assertNull($activity->lastLogin($user));
        $this->assertFalse($activity->isOnline($user));
    }

    public function test_activity_is_shown_in_all_frameworks_without_changing_search_json(): void
    {
        $this->migrate();
        config(['laravelusers.activity.login' => true, 'laravelusers.activity.online' => true]);
        $user = $this->user();
        $activity = $this->app->make(UserActivity::class);
        $activity->recordLogin($user, $this->request());
        $this->actingAs($user);
        foreach (['bootstrap4', 'bootstrap5', 'tailwind'] as $framework) {
            config(['laravelusers.frontend' => $framework]);
            $this->get('/users/'.$user->id)->assertOk()->assertSee('192.0.2.10')->assertSee('Chrome 130.0.0')->assertSee('Last login');
            $this->get('/users')->assertOk()->assertSee('Online');
        }
        $this->postJson('/search-users', ['user_search_box' => $user->name])->assertOk()->assertExactJson([$user->toArray()]);
        config(['laravelusers.activity.login' => false, 'laravelusers.activity.online' => false]);
        $this->get('/users/'.$user->id)->assertOk()->assertDontSee('Last login')->assertDontSee('Login IP address');
    }

    public function test_activity_migration_rolls_back_without_changing_users(): void
    {
        $user = $this->user();
        $migration = require dirname(__DIR__, 2).'/src/database/migrations/2026_10_07_000000_create_laravelusers_login_activity_table.php';
        $migration->up();
        $this->assertTrue(Schema::hasTable('laravelusers_login_activity'));
        $migration->down();
        $this->assertFalse(Schema::hasTable('laravelusers_login_activity'));
        $this->assertSame($user->email, $user->fresh()->email);
        $this->assertFalse(Schema::hasColumn('users', 'last_login_at'));
    }

    public function test_presence_cleanup_still_runs_when_login_storage_is_unavailable(): void
    {
        config(['laravelusers.activity.online' => true]);
        $user = $this->user();
        $this->request();
        Event::dispatch(new Authenticated('web', $user));
        $this->assertTrue($this->app->make(UserActivity::class)->isOnline($user));
        config(['laravelusers.activity.login' => true]);
        $this->mock(ExceptionHandler::class, function ($mock) {
            $mock->shouldReceive('report')->once();
        });
        $user->delete();
        $this->assertFalse($this->app->make(UserActivity::class)->isOnline($user));
    }

    public function test_listing_loads_login_times_in_one_query_and_search_metadata_is_opt_in(): void
    {
        $this->migrate();
        config(['laravelusers.activity.login' => true, 'laravelusers.activity.online' => true, 'laravelusers.paginateListSize' => 1]);
        $admin = $this->user();
        $other = $this->user(['name' => 'RemoteAccount']);
        $activity = $this->app->make(UserActivity::class);
        $activity->recordLogin($other, $this->request());
        DB::enableQueryLog();
        $records = $activity->listing([$admin, $other]);
        $queries = array_filter(DB::getQueryLog(), fn ($query) => str_contains($query['query'], 'laravelusers_login_activity'));
        $this->assertCount(1, $queries);
        $this->assertNull($records[$admin->id]['last_login_at']);
        $this->assertNotNull($records[$other->id]['last_login_at']);
        $this->actingAs($admin);
        $this->postJson('/search-users', ['user_search_box' => 'RemoteAccount'])->assertExactJson([$other->toArray()]);
        $this->postJson('/search-users', ['user_search_box' => 'RemoteAccount', 'include_activity' => 1])->assertOk()
            ->assertJsonPath('users.0.id', $other->id)->assertJsonPath('activity.'.$other->id.'.online', false)
            ->assertJsonPath('activity.'.$other->id.'.last_login_at', $records[$other->id]['last_login_at'])
            ->assertDontSee('192.0.2.10')->assertDontSee('Chrome');
        $this->postJson('/search-users', ['user_search_box' => 'RemoteAccount', 'include_activity' => 1, 'include_login_details' => 1])->assertOk()
            ->assertJsonPath('activity.'.$other->id.'.ip_address', '192.0.2.10')->assertSee('Chrome');
        config(['laravelusers.showLastLoginDetailsColumn' => false]);
        $this->postJson('/search-users', ['user_search_box' => 'RemoteAccount', 'include_activity' => 1, 'include_login_details' => 1])->assertOk()->assertDontSee('192.0.2.10')->assertDontSee('Chrome');
        DB::disableQueryLog();
    }

    public function test_activity_columns_can_be_hidden_without_disabling_tracking(): void
    {
        $this->migrate();
        $this->actingAs($this->user());
        config(['laravelusers.activity.login' => true, 'laravelusers.activity.online' => true, 'laravelusers.showOnlineColumn' => false, 'laravelusers.showLastLoginColumn' => false]);
        foreach (['bootstrap4', 'bootstrap5', 'tailwind'] as $framework) {
            config(['laravelusers.frontend' => $framework]);
            $response = $this->get('/users')->assertOk();
            $this->assertDoesNotMatchRegularExpression('/<th[^>]*>\s*Status\s*<\/th>/', $response->getContent());
            $this->assertDoesNotMatchRegularExpression('/<th[^>]*>\s*Last login\s*<\/th>/', $response->getContent());
        }
    }
}
