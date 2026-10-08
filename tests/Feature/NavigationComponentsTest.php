<?php

namespace jeremykenedy\laravelusers\Test\Feature;

use Illuminate\Support\Facades\Blade;
use jeremykenedy\laravelusers\Support\AccountPreferences;
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
}
