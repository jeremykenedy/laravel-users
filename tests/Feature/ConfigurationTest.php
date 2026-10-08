<?php

namespace jeremykenedy\laravelusers\Test\Feature;

use Illuminate\Support\Env;
use jeremykenedy\laravelusers\Support\Frontend;
use jeremykenedy\laravelusers\Test\TestCase;

class ConfigurationTest extends TestCase
{
    public function test_all_original_config_keys_and_defaults_are_preserved(): void
    {
        $original = ['authEnabled' => true, 'rolesEnabled' => false, 'rolesMiddlwareEnabled' => true, 'rolesMiddlware' => 'role:admin', 'softDeletedEnabled' => false, 'enablePagination' => true, 'paginateListSize' => 25, 'enableSearchUsers' => true, 'enabledDatatablesJs' => false, 'datatablesJsStartCount' => 25, 'tooltipsEnabled' => true, 'fontAwesomeEnabled' => true, 'enablePackageBootstapAlerts' => true];
        $config = require __DIR__.'/../../src/config/laravelusers.php';
        foreach ($original as $key => $default) {
            $this->assertSame($default, $config[$key]);
        }
        $this->assertSame('bootstrap4', $config['frontend']);
        $this->assertFalse($config['activity']['online']);
        $this->assertFalse($config['activity']['login']);
    }

    public function test_environment_values_override_boolean_number_string_and_nested_defaults(): void
    {
        $values = ['LARAVEL_USERS_SHOW_LOGOUT' => 'false', 'LARAVEL_USERS_SEARCH_DEBOUNCE' => '3000', 'LARAVEL_USERS_HEADER_VIEW' => 'dashboard.header', 'LARAVEL_USERS_ACTIVITY_ONLINE' => 'true', 'LARAVEL_USERS_WELCOME_ENABLED' => 'false'];

        try {
            foreach ($values as $name => $value) {
                Env::getRepository()->set($name, $value);
            }
            $config = require __DIR__.'/../../src/config/laravelusers.php';
            $this->assertFalse($config['showLogout']);
            $this->assertSame('3000', $config['searchDebounce']);
            $this->assertSame('dashboard.header', $config['headerView']);
            $this->assertTrue($config['activity']['online']);
            $this->assertFalse($config['welcome']['enabled']);
        } finally {
            foreach ($values as $name => $value) {
                Env::getRepository()->clear($name);
            }
        }
    }

    public function test_header_footer_and_logout_options_work_in_every_framework(): void
    {
        $this->actingAs($this->user(['name' => 'HeaderPerson']));
        foreach (Frontend::FRAMEWORKS as $framework) {
            config(['laravelusers.frontend' => $framework, 'laravelusers.showLogout' => false, 'laravelusers.fullWidth' => true]);
            $this->get('/users')->assertOk()->assertDontSee('action="http://localhost/logout"', false)->assertSee('data-lu-full-width="true"', false);
            config(['laravelusers.headerView' => 'header', 'laravelusers.footerView' => 'footer']);
            $this->get('/users')->assertOk()->assertSee('Dashboard header')->assertSee('Dashboard footer');
            config(['laravelusers.showHeader' => false]);
            $this->get('/users')->assertOk()->assertDontSee('Dashboard header')->assertSee('Dashboard footer');
            config(['laravelusers.showHeader' => true, 'laravelusers.headerView' => null, 'laravelusers.footerView' => null]);
        }
    }

    public function test_new_view_controls_can_be_disabled(): void
    {
        $this->actingAs($this->user());
        foreach (['bootstrap5', 'tailwind'] as $framework) {
            config(['laravelusers.frontend' => $framework, 'laravelusers.iconsEnabled' => false, 'laravelusers.emailLinks' => false, 'laravelusers.showUserCount' => false, 'laravelusers.welcome.enabled' => false, 'laravelusers.confirmSave' => false, 'laravelusers.confirmDelete' => false]);
            $this->get('/users')->assertOk()->assertDontSee('href="mailto:', false)->assertDontSee('Total users:')->assertDontSee('id="lu-confirmation"', false)->assertDontSee('class="lu-icon"', false);
            $this->get('/users/create')->assertOk()->assertDontSee('name="send_welcome_email"', false)->assertDontSee('class="lu-input-icon"', false);
        }
    }
}
