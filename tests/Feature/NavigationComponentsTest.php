<?php

namespace jeremykenedy\laravelusers\Test\Feature;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use jeremykenedy\laravelusers\Support\AccountPreferences;
use jeremykenedy\laravelusers\Support\UserActivity;
use jeremykenedy\laravelusers\Test\TestCase;

class NavigationComponentsTest extends TestCase
{
    public function test_theme_toggle_can_target_an_application_layout_without_the_package_shell(): void
    {
        $html = Blade::render('<x-laravelusers::theme-toggle id="dashboard-theme" target="#dashboard" class="ml-auto" />');
        $this->assertStringContainsString('id="dashboard-theme"', $html);
        $this->assertStringContainsString('data-lu-theme-target="#dashboard"', $html);
        $this->assertStringContainsString('lu-theme-component ml-auto', $html);
        $this->assertStringContainsString("root.classList.toggle('dark'", $html);
    }

    public function test_user_menu_uses_the_current_avatar_and_an_optional_logout_route(): void
    {
        $this->actingAs($this->user(['name' => 'Navigation User']));
        config(['laravelusers.avatar.source' => 'initials']);
        $html = Blade::render('<x-laravelusers::user-menu /><x-laravelusers::user-menu :show-logout="false" />@stack("laravelusers-components-scripts")');
        $this->assertSame(2, substr_count($html, 'lu-avatar lu-navigation-avatar'));
        $this->assertStringContainsString('NU', $html);
        $this->assertSame(1, substr_count($html, 'action="'.route('logout').'"'));
        $this->assertStringContainsString('name="_token"', $html);
        $this->assertSame(1, substr_count($html, "document.querySelectorAll(':where(#laravelusers, .lu-user-menu-component)"));
    }

    public function test_guests_do_not_receive_a_username_or_logout_form(): void
    {
        $html = Blade::render('<x-laravelusers::user-menu />');
        $this->assertStringNotContainsString('<details', $html);
        $this->assertStringNotContainsString('action="'.route('logout').'"', $html);
    }

    public function test_account_link_respects_the_current_users_override_and_works_without_logout(): void
    {
        $user = $this->user();
        $this->actingAs($user);
        foreach (glob(dirname(__DIR__, 2).'/src/database/accounts/*.php') as $path) {
            (require $path)->up();
        }
        $this->assertStringNotContainsString('href="'.route('users.account').'"', Blade::render('<x-laravelusers::user-menu />'));
        AccountPreferences::save($user, ['account_enabled' => 'on', 'account_settings_enabled' => 'on']);
        $html = Blade::render('<x-laravelusers::user-menu :show-logout="false" />');
        $this->assertStringContainsString('href="'.route('users.account').'"', $html);
        $this->assertStringContainsString('Account Settings', $html);
        $this->assertStringNotContainsString('action="'.route('logout').'"', $html);
        $this->get('/users/account')->assertOk();
        AccountPreferences::save($user, ['account_enabled' => 'off']);
        $this->assertStringNotContainsString('href="'.route('users.account').'"', Blade::render('<x-laravelusers::user-menu />'));
        $this->get('/users/account')->assertNotFound();
    }

    public function test_user_menu_shows_sign_in_details_without_linking_its_ip_address(): void
    {
        (require dirname(__DIR__, 2).'/src/database/migrations/2026_10_07_000000_create_laravelusers_login_activity_table.php')->up();
        config(['laravelusers.activity.login' => true]);
        $user = $this->user(['name' => 'Recent Login']);
        $this->actingAs($user);
        $request = Request::create('/', 'GET', [], [], [], [
            'REMOTE_ADDR'     => '192.0.2.40',
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/130.0.0.0 Safari/537.36',
        ]);
        $this->app->make(UserActivity::class)->recordLogin($user, $request);

        $html = Blade::render('<x-laravelusers::user-menu />');

        $this->assertStringContainsString('lu-user-menu-login', $html);
        $this->assertStringContainsString('192.0.2.40', $html);
        $this->assertStringContainsString('Windows 10', $html);
        $this->assertStringContainsString('Chrome 130.0.0', $html);
        $this->assertStringNotContainsString('ipinfo.io', $html);
        $this->assertStringContainsString('data-lu-time', $html);
    }

    public function test_invalid_ip_addresses_are_not_linked_to_the_lookup_service(): void
    {
        $html = Blade::render('@include("laravelusers::partials.ip-address", ["ip" => "javascript:alert(1)"])');

        $this->assertStringContainsString('javascript:alert(1)', $html);
        $this->assertStringNotContainsString('<a ', $html);
    }

    public function test_management_link_respects_host_gate_middleware_and_invalid_ability_names(): void
    {
        $this->actingAs($this->user());
        config(['laravelusers.rolesEnabled' => false, 'laravelusers.middleware' => ['auth', 'can:manage-users']]);
        Gate::define('manage-users', fn () => true);
        $this->assertStringContainsString('href="'.route('users').'"', Blade::render('<x-laravelusers::user-menu />'));

        Gate::define('manage-users', fn () => false);
        $this->assertStringNotContainsString('href="'.route('users').'"', Blade::render('<x-laravelusers::user-menu />'));

        Gate::define('manage-users', fn () => true);
        config(['laravelusers.middleware' => ['can:,manage-users']]);
        $this->assertStringNotContainsString('href="'.route('users').'"', Blade::render('<x-laravelusers::user-menu />'));
    }

    public function test_impersonation_is_not_offered_or_available_without_a_roles_integration(): void
    {
        config(['laravelusers.impersonation.enabled' => true]);
        $actor = $this->user(['name' => 'Admin Candidate']);
        $target = $this->user(['name' => 'Target User']);
        $this->actingAs($actor);

        $this->assertStringNotContainsString('secret-agent', Blade::render('<x-laravelusers::user-menu />'));
        $this->post('/users/'.$target->getKey().'/impersonate')->assertNotFound();
    }
}
