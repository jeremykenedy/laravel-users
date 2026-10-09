<?php

namespace jeremykenedy\laravelusers\Test\Feature;

use Illuminate\Support\Env;
use jeremykenedy\laravelusers\Support\Frontend;
use jeremykenedy\laravelusers\Test\TestCase;

class ConfigurationTest extends TestCase
{
    private const ORIGINAL_DEFAULTS = [
        'laravelUsersBladeExtended'   => 'laravelusers::layouts.app',
        'authEnabled'                 => true,
        'rolesEnabled'                => false,
        'rolesMiddlwareEnabled'       => true,
        'rolesMiddlware'              => 'role:admin',
        'roleModel'                   => 'jeremykenedy\LaravelRoles\Models\Role',
        'softDeletedEnabled'          => false,
        'defaultUserModel'            => 'App\Models\User',
        'showUsersBlade'              => 'laravelusers::usersmanagement.show-users',
        'createUserBlade'             => 'laravelusers::usersmanagement.create-user',
        'showIndividualUserBlade'     => 'laravelusers::usersmanagement.show-user',
        'editIndividualUserBlade'     => 'laravelusers::usersmanagement.edit-user',
        'enablePackageBootstapAlerts' => true,
        'enablePagination'            => true,
        'paginateListSize'            => 25,
        'enableSearchUsers'           => true,
        'enabledDatatablesJs'         => false,
        'datatablesJsStartCount'      => 25,
        'datatablesCssCDN'            => 'https://cdn.datatables.net/1.10.12/css/dataTables.bootstrap.min.css',
        'datatablesJsCDN'             => 'https://cdn.datatables.net/1.10.12/js/jquery.dataTables.min.js',
        'datatablesJsPresetCDN'       => 'https://cdn.datatables.net/1.10.12/js/dataTables.bootstrap.min.js',
        'tooltipsEnabled'             => true,
        'enableBootstrapPopperJsCdn'  => true,
        'bootstrapPopperJsCdn'        => 'https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js',
        'fontAwesomeEnabled'          => true,
        'fontAwesomeCdn'              => 'https://use.fontawesome.com/releases/v5.0.6/css/all.css',
        'enableBootstrapCssCdn'       => true,
        'bootstrapCssCdn'             => 'https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css',
        'enableAppCss'                => true,
        'appCssPublicFile'            => 'css/app.css',
        'enableBootstrapJsCdn'        => true,
        'bootstrapJsCdn'              => 'https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js',
        'enableAppJs'                 => true,
        'appJsPublicFile'             => 'js/app.js',
        'enablejQueryCdn'             => true,
        'jQueryCdn'                   => 'https://code.jquery.com/jquery-3.3.1.min.js',
    ];

    public function test_all_original_config_keys_and_defaults_are_preserved(): void
    {
        $config = require __DIR__.'/../../src/config/laravelusers.php';
        foreach (self::ORIGINAL_DEFAULTS as $key => $default) {
            $this->assertSame($default, $config[$key]);
        }
        $this->assertSame('bootstrap4', $config['frontend']);
        $this->assertFalse($config['showBreadcrumbs']);
        foreach (['emails.enabled', 'welcome.enabled', 'account.enabled', 'account.settings_enabled', 'account_links.enabled', 'cleanup.enabled', 'settings.enabled', 'settings.packages.enabled', 'appearance.per_user', 'avatar.enabled', 'bulkActions', 'tableViewToggle', 'responsiveTable', 'responsiveButtons', 'columnVisibility', 'tableSorting', 'tableFiltering', 'themeToggle', 'localizeDates', 'searchDebounceEnabled', 'emailLinks', 'showOnlineColumn', 'showLastLoginColumn', 'showLastLoginDetailsColumn', 'showUserCount', 'password.meter', 'password.confirmation_feedback'] as $key) {
            $this->assertFalse(data_get($config, $key), $key.' must remain opt-in.');
        }
        $this->assertFalse($config['activity']['online']);
        $this->assertFalse($config['activity']['login']);
        $this->assertFalse($config['avatar']['per_user']);
        $this->assertFalse($config['permissionsEnabled']);
        $this->assertNull($config['password']['create_max']);
        $this->assertFalse($config['tableViewToggle']);
        $this->assertSame(['mobile' => 1, 'tablet' => 2, 'desktop' => 3, 'wide' => 4], $config['cardColumns']);
        $this->assertNull($config['emails']['reset_expire']);
        foreach (['profileCardDarkColor', 'profileCardDarkGradient', 'profileCardDarkGradientStrength', 'editCardDarkColor', 'editCardDarkGradient', 'editCardDarkGradientStrength'] as $key) {
            $this->assertNull($config[$key]);
        }
    }

    public function test_environment_values_override_boolean_number_string_and_nested_defaults(): void
    {
        $values = ['LARAVEL_USERS_SHOW_LOGOUT' => 'false', 'LARAVEL_USERS_SHOW_BREADCRUMBS' => 'true', 'LARAVEL_USERS_SEARCH_DEBOUNCE_ENABLED' => 'false', 'LARAVEL_USERS_TABLE_BUTTONS_ICON_ONLY' => 'true', 'LARAVEL_USERS_SEARCH_DEBOUNCE' => '3000', 'LARAVEL_USERS_HEADER_VIEW' => 'dashboard.header', 'LARAVEL_USERS_ACTIVITY_ONLINE' => 'true', 'LARAVEL_USERS_WELCOME_ENABLED' => 'false', 'LARAVEL_USERS_PROFILE_CARD_COLOR' => '#264e36', 'LARAVEL_USERS_TABLE_VIEW_TOGGLE' => 'true', 'LARAVEL_USERS_CARD_COLUMNS_TABLET' => '3', 'LARAVEL_USERS_EDIT_CARD_COLOR' => '#765400', 'LARAVEL_USERS_PASSWORD_MIN' => '12', 'LARAVEL_USERS_PASSWORD_NUMBERS' => 'true', 'LARAVEL_USERS_PASSWORD_METER' => 'false', 'LARAVEL_USERS_EMAILS_ENABLED' => 'false', 'LARAVEL_USERS_EMAIL_GREETING' => 'Hello', 'LARAVEL_USERS_EMAIL_USE_SIGNOFF' => 'false'];

        $values['LARAVEL_USERS_PROFILE_CARD_DARK_COLOR'] = '#19283a';
        $values['LARAVEL_USERS_PROFILE_CARD_DARK_GRADIENT'] = 'false';
        $values['LARAVEL_USERS_PROFILE_CARD_DARK_GRADIENT_STRENGTH'] = '75';
        $values['LARAVEL_USERS_EDIT_CARD_DARK_COLOR'] = '#49340e';
        $values['LARAVEL_USERS_EDIT_CARD_DARK_GRADIENT'] = 'true';
        $values['LARAVEL_USERS_EDIT_CARD_DARK_GRADIENT_STRENGTH'] = '0';
        $values['LARAVEL_USERS_AVATAR_PER_USER'] = 'true';
        $values['LARAVEL_USERS_PERMISSIONS_ENABLED'] = 'true';
        $values['LARAVEL_USERS_PASSWORD_CREATE_MAX'] = '64';
        $values['LARAVEL_USERS_EMAIL_RESET_EXPIRE'] = '30';
        $values['LARAVEL_USERS_EMAIL_RECIPIENT_HEIGHT'] = '120';

        try {
            foreach ($values as $name => $value) {
                Env::getRepository()->set($name, $value);
            }
            $config = require __DIR__.'/../../src/config/laravelusers.php';
            $this->assertEnvironmentOverrides($config);
        } finally {
            foreach ($values as $name => $value) {
                Env::getRepository()->clear($name);
            }
        }
    }

    private function assertEnvironmentOverrides(array $config): void
    {
        $this->assertSame('#19283a', $config['profileCardDarkColor']);
        $this->assertFalse($config['profileCardDarkGradient']);
        $this->assertSame('75', $config['profileCardDarkGradientStrength']);
        $this->assertSame('#49340e', $config['editCardDarkColor']);
        $this->assertTrue($config['editCardDarkGradient']);
        $this->assertSame('0', $config['editCardDarkGradientStrength']);
        $this->assertTrue($config['avatar']['per_user']);
        $this->assertTrue($config['permissionsEnabled']);
        $this->assertSame('64', $config['password']['create_max']);
        $this->assertFalse($config['showLogout']);
        $this->assertTrue($config['showBreadcrumbs']);
        $this->assertFalse($config['searchDebounceEnabled']);
        $this->assertTrue($config['tableButtonsIconOnly']);
        $this->assertSame('3000', $config['searchDebounce']);
        $this->assertSame('dashboard.header', $config['headerView']);
        $this->assertTrue($config['activity']['online']);
        $this->assertFalse($config['welcome']['enabled']);
        $this->assertSame('#264e36', $config['profileCardColor']);
        $this->assertTrue($config['tableViewToggle']);
        $this->assertSame('3', $config['cardColumns']['tablet']);
        $this->assertSame('#765400', $config['editCardColor']);
        $this->assertSame('12', $config['password']['min']);
        $this->assertTrue($config['password']['numbers']);
        $this->assertFalse($config['password']['meter']);
        $this->assertFalse($config['emails']['enabled']);
        $this->assertSame('Hello', $config['emails']['greeting']);
        $this->assertFalse($config['emails']['use_signoff']);
        $this->assertSame('30', $config['emails']['reset_expire']);
        $this->assertSame('120', $config['emails']['recipient_height']);
    }

    public function test_partial_host_configuration_does_not_enable_new_email_or_password_controls(): void
    {
        config(['laravelusers.emails' => ['message' => true], 'laravelusers.welcome' => ['force_password_reset' => true], 'laravelusers.password' => ['min' => 6, 'max' => 20]]);
        $this->actingAs($this->user());
        foreach (Frontend::FRAMEWORKS as $framework) {
            config(['laravelusers.frontend' => $framework]);
            $this->get('/users/create')->assertOk()->assertDontSee('name="send_welcome_email"', false)->assertDontSee('data-lu-password-meter', false)->assertDontSee('id="lu-password-confirmation-error"', false);
            $this->postJson('/users/email', ['action' => 'message', 'ids' => [1], 'subject' => 'Notice', 'message' => 'Account notice'])->assertForbidden();
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
