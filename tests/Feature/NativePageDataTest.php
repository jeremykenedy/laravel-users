<?php

namespace jeremykenedy\laravelusers\Test\Feature;

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use InvalidArgumentException;
use jeremykenedy\laravelusers\Support\NativePageData;
use jeremykenedy\laravelusers\Test\TestCase;

/**
 * PHPUnit requires public methods for these independent behavior and regression scenarios.
 *
 * @SuppressWarnings("PHPMD.TooManyPublicMethods")
 */
class NativePageDataTest extends TestCase
{
    public function test_native_list_uses_explicit_user_fields_and_existing_mutation_routes(): void
    {
        $actor = $this->user();
        $target = $this->user(['name' => 'NativeUser']);
        $target->setAttribute('api_token', 'private-token');
        $this->actingAs($actor);
        config(['laravelusers.avatar.enabled' => true, 'laravelusers.avatar.source' => 'initials']);
        $page = $this->page('laravelusers::usersmanagement.show-users', ['users' => collect([$actor, $target])]);

        $this->assertSame('users', $page['screen']);
        $this->assertSame('bootstrap4', $page['framework']);
        $this->assertSame('NativeUser', $page['data']['users'][1]['name']);
        $this->assertSame('N', $page['data']['users'][1]['avatar']['initials']);
        $this->assertSame(route('user.destroy', $target->id), $page['data']['users'][1]['actions'][0]['url']);
        $this->assertSame('DELETE', $page['forms']['delete-user']['method']);
        $this->assertStringNotContainsString('private-token', json_encode($page));
        $this->assertStringNotContainsString($actor->password, json_encode($page));
        $this->assertArrayNotHasKey('remember_token', $page['data']['users'][0]);
        $this->assertNotContains('delete', array_column($page['data']['users'][0]['actions'], 'name'));
        $this->assertArrayNotHasKey('impersonate-user', $page['forms']);
        $this->assertArrayNotHasKey('impersonate_users', $page['capabilities']);
        $this->assertArrayNotHasKey('banner', $page['data']);
        $this->assertStringNotContainsString('/impersonate', json_encode($page));
    }

    public function test_user_form_reuses_old_non_secret_values_and_server_validation_errors(): void
    {
        $user = $this->user();
        $this->actingAs($user);
        $request = $this->request();
        $request->session()->flashInput(['name' => 'RetryName', 'email' => 'retry@example.com', 'password' => 'never-render-this', 'password_confirmation' => 'never-render-this']);
        $request->session()->put('errors', (new ViewErrorBag())->put('default', new MessageBag(['email' => 'Address already used.'])));
        $page = $this->app->make(NativePageData::class)->forView('laravelusers::modern.edit-user', ['user' => $user, 'rolesEnabled' => false], $request);

        $this->assertSame('RetryName', $page['forms']['user']['values']['name']);
        $this->assertSame('', $page['forms']['user']['values']['password']);
        $this->assertSame('', $page['forms']['user']['values']['password_confirmation']);
        $this->assertSame(['Address already used.'], $page['forms']['user']['errors']['email']);
        $this->assertSame(route('users.update', $user->id), $page['forms']['user']['action']);
        $this->assertStringNotContainsString('never-render-this', json_encode($page));
    }

    public function test_disabled_features_do_not_offer_email_or_account_actions(): void
    {
        $actor = $this->user();
        $target = $this->user();
        $this->actingAs($actor);
        config(['laravelusers.emails.enabled' => false, 'laravelusers.account.enabled' => false, 'laravelusers.settings.enabled' => false]);
        $page = $this->page('laravelusers::modern.show-users', ['users' => collect([$target])]);

        $this->assertNotContains('email-message', array_column($page['data']['users'][0]['actions'], 'name'));
        $this->assertArrayNotHasKey('email-message', $page['forms']);
        $this->assertNotContains(route('users.account'), array_column($page['data']['navigation'], 'url'));
        $this->assertNotContains(route('users.settings'), array_column($page['data']['navigation'], 'url'));
        $this->assertArrayNotHasKey('email', $page['urls']);
        $this->assertArrayNotHasKey('email_preview', $page['urls']);
        $this->assertArrayNotHasKey('account', $page['urls']);
        $this->assertArrayNotHasKey('settings', $page['urls']);
        $this->assertArrayNotHasKey('restore-user', $page['forms']);
        $this->assertArrayNotHasKey('force-delete-user', $page['forms']);
    }

    public function test_denied_management_actions_have_no_native_forms_or_urls(): void
    {
        $actor = $this->user();
        $target = $this->user();
        $this->actingAs($actor);
        config(['laravelusers.settings.enabled' => true, 'laravelusers.access.create_users.mode' => 'deny', 'laravelusers.access.delete_users.mode' => 'deny', 'laravelusers.emails.enabled' => false, 'laravelusers.bulkActions' => false]);
        $page = $this->page('laravelusers::modern.show-users', ['users' => collect([$target])]);

        $this->assertArrayNotHasKey('create', $page['urls']);
        $this->assertArrayNotHasKey('bulk', $page['urls']);
        $this->assertArrayNotHasKey('delete-user', $page['forms']);
        $this->assertSame([], $page['features']['bulk_actions']);
    }

    public function test_settings_forms_keep_nested_names_and_typed_security_confirmations(): void
    {
        $this->actingAs($this->user());
        config(['laravelusers.settings.enabled' => true, 'laravelusers.softDeletedEnabled' => true]);
        Gate::define('manage-laravelusers-settings', fn () => true);
        $page = $this->page('laravelusers::modern.settings', ['settingsAvailable' => true, 'accessAvailable' => false, 'packageManagementAllowed' => true, 'packageQueueReady' => true, 'managedPackages' => ['toast' => false, 'laravel-roles' => false, 'spatie' => false]]);

        $fields = collect($page['forms']['email-templates']['fields']);
        $this->assertSame('templates[welcome][subject]', $fields->firstWhere('key', 'templates.welcome.subject')['name']);
        $this->assertSame('permanently delete', collect($page['forms']['cleanup']['fields'])->firstWhere('key', 'confirmation')['required_text']);
        $this->assertSame('change', collect($page['forms']['accounts-apply-enabled']['fields'])->firstWhere('key', 'confirmation')['required_text']);
        $this->assertSame('continue', collect($page['forms']['package-toast']['fields'])->firstWhere('key', 'confirmation')['required_text']);
        $this->assertSame(route('users.settings.packages'), $page['forms']['package-toast']['action']);
    }

    public function test_account_and_public_confirmation_expose_only_display_values(): void
    {
        $user = $this->user();
        $this->actingAs($user);
        $page = $this->page('laravelusers::account.page', ['user' => $user, 'accountEditable' => true, 'fullName' => 'Native Person', 'pendingEmail' => (object) ['new_email' => 'new@example.com', 'token_hash' => 'private-hash']]);

        $this->assertSame('new@example.com', $page['data']['user']['pending_email']);
        $this->assertSame('Native Person', $page['forms']['account-profile']['values']['full_name']);
        $this->assertSame('delete', collect($page['forms']['account-delete']['fields'])->firstWhere('key', 'confirmation')['required_text']);
        $this->assertStringNotContainsString('private-hash', json_encode($page));

        $confirmation = $this->page('laravelusers::account-links.confirm', ['link' => (object) ['action' => 'restore', 'token_hash' => 'private-hash'], 'token' => 'one-time-link']);
        $this->assertSame('account-link', $confirmation['screen']);
        $this->assertSame([], $confirmation['data']['navigation']);
        $this->assertSame(route('users.account-link.confirm', ['token' => 'one-time-link']), $confirmation['forms']['confirmation']['action']);
        $this->assertStringNotContainsString('private-hash', json_encode($confirmation));

        $email = $this->page('laravelusers::account.confirm-email', ['change' => (object) ['token_hash' => 'private-hash'], 'token' => 'email-token']);
        $this->assertSame([], $email['data']['navigation']);
        $this->assertSame([], $email['capabilities']);
        $this->assertSame(['login'], array_keys($email['urls']));
        $this->assertArrayNotHasKey('current_user', $email['data']);
    }

    public function test_custom_application_views_are_not_reinterpreted_as_native_screens(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->page('custom', []);
    }

    public function test_breadcrumb_toggle_is_off_by_default_and_enabled_path_keeps_the_active_item_unlinked(): void
    {
        $user = $this->user(['name' => 'Breadcrumb User']);
        $this->actingAs($user);
        config(['laravelusers.settings.enabled' => true]);
        $settings = $this->page('laravelusers::modern.settings', ['settingsAvailable' => true]);
        $this->assertFalse($settings['features']['breadcrumbs']);
        $this->assertFalse($settings['forms']['settings']['values']['show_breadcrumbs']);
        $this->assertArrayNotHasKey('breadcrumbs', $settings['data']);

        config(['laravelusers.showBreadcrumbs' => true]);
        $page = $this->page('laravelusers::modern.edit-user', ['user' => $user, 'rolesEnabled' => false]);
        $crumbs = $page['data']['breadcrumbs'];
        $this->assertSame(route('users'), $crumbs[0]['url']);
        $this->assertFalse($crumbs[0]['native']);
        $this->assertSame(route('users'), $crumbs[1]['url']);
        $this->assertSame(route('users.show', $user->id), $crumbs[2]['url']);
        $this->assertArrayNotHasKey('url', $crumbs[3]);
    }

    public function test_native_highlight_controls_preserve_light_defaults_dark_inheritance_and_column_availability(): void
    {
        $user = $this->user();
        $this->actingAs($user);
        $settings = $this->page('laravelusers::modern.settings', ['settingsAvailable' => true]);
        $this->assertSame('#ffffff', $settings['forms']['settings']['values']['profile_gradient_highlight_color']);
        $this->assertNull($settings['forms']['settings']['values']['profile_dark_gradient_highlight_color']);
        $this->assertTrue(collect($settings['forms']['settings']['fields'])->firstWhere('key', 'profile_dark_gradient_highlight_color')['nullable']);
        $this->assertSame('profile_gradient_highlight_color', collect($settings['forms']['settings']['fields'])->firstWhere('key', 'profile_dark_gradient_highlight_color')['inherit_from']);

        $data = ['user' => $user, 'rolesEnabled' => false, 'appearanceEnabled' => true, 'appearanceAvailable' => true, 'appearanceDarkAvailable' => true, 'appearanceHighlightAvailable' => false];
        $page = $this->page('laravelusers::modern.edit-user', $data);
        $this->assertNotContains('user_card_gradient_highlight_color', array_column($page['forms']['user']['fields'], 'key'));
        $data['appearanceHighlightAvailable'] = true;
        $page = $this->page('laravelusers::modern.edit-user', $data);
        $this->assertNull($page['forms']['user']['values']['user_card_gradient_highlight_color']);
        $this->assertNull($page['forms']['user']['values']['user_card_dark_gradient_highlight_color']);
    }

    public function test_package_status_projection_omits_host_details_and_is_only_offered_to_authorized_settings_views(): void
    {
        $this->actingAs($this->user());
        $operation = ['id' => 'operation-id', 'status_url' => url('/users/settings/packages/operation-id'), 'status' => 'queued', 'stage' => 'queue', 'package' => 'toast', 'operation' => 'install', 'message' => 'Waiting for the worker.', 'queued_at' => '2026-10-08T21:00:00Z', 'command' => '/private/host/composer install'];
        $page = $this->page('laravelusers::modern.settings', ['settingsAvailable' => true, 'packageManagementAllowed' => true, 'packageOperation' => $operation]);
        $this->assertSame('queued', $page['data']['package_operation']['status']);
        $this->assertArrayNotHasKey('command', $page['data']['package_operation']);
        $page = $this->page('laravelusers::modern.settings', ['settingsAvailable' => true, 'packageManagementAllowed' => false, 'packageOperation' => $operation]);
        $this->assertArrayNotHasKey('package_operation', $page['data']);
        $this->assertStringNotContainsString('operation-id', json_encode($page));
    }

    public function test_native_package_controls_follow_worker_readiness_installed_packages_and_existing_http_actions(): void
    {
        $this->actingAs($this->user());
        $data = ['settingsAvailable' => true, 'packageManagementAllowed' => true, 'packageQueueReady' => false, 'packageRequirements' => ['status' => 'checking', 'queue_ready' => false, 'message' => 'Waiting for the worker.', 'host_path' => '/private/host'], 'managedPackages' => ['toast' => false, 'laravel-roles' => true, 'spatie' => false]];
        $page = $this->page('laravelusers::modern.settings', $data);

        $this->assertSame(route('users.settings.packages.verify'), $page['forms']['package-verify']['action']);
        $this->assertSame(['package' => 'requirements', 'operation' => 'verify'], $page['forms']['package-verify']['values']);
        $this->assertArrayNotHasKey('host_path', $page['data']['packages']['requirements']);
        $this->assertTrue($page['forms']['package-toast']['disabled']);
        $this->assertNotContains('setup', array_column($page['forms']['package-toast']['fields'], 'key'));
        $this->assertNotContains('migrate', array_column($page['forms']['package-toast']['fields'], 'key'));
        $this->assertTrue($page['forms']['package-spatie']['blocked']);
        $this->assertSame('configure', $page['forms']['package-laravel-roles-configure']['values']['operation']);
        $this->assertSame('continue', collect($page['forms']['package-laravel-roles-configure']['fields'])->firstWhere('key', 'confirmation')['required_text']);
        $this->assertSame(route('users.settings.packages'), $page['forms']['package-laravel-roles-configure']['action']);
        $this->assertArrayNotHasKey('package-spatie-configure', $page['forms']);

        $data['packageQueueReady'] = true;
        $page = $this->page('laravelusers::modern.settings', $data);
        $supported = PHP_VERSION_ID >= 80200 && version_compare(Application::VERSION, '10.0.0', '>=');
        $this->assertSame(!$supported, $page['forms']['package-toast']['disabled']);
        if (!$supported) {
            $this->assertSame(trans('laravelusers::ui.packages_toast_unsupported'), collect($page['data']['packages']['choices'])->firstWhere('package', 'toast')['reason']);
        }
        $this->assertFalse($page['forms']['package-laravel-roles-configure']['disabled']);
        $this->assertTrue($page['forms']['package-spatie']['disabled']);
    }

    public function test_completed_toast_setup_has_no_configure_action_while_roles_setup_remains_available(): void
    {
        $this->actingAs($this->user());
        $data = ['settingsAvailable' => true, 'packageManagementAllowed' => true, 'packageQueueReady' => true, 'managedPackages' => ['toast' => true, 'laravel-roles' => true, 'spatie' => false], 'managedPackageSetup' => ['toast' => false]];
        $page = $this->page('laravelusers::modern.settings', $data);
        $this->assertArrayHasKey('package-toast-configure', $page['forms']);

        $data['managedPackageSetup']['toast'] = true;
        $page = $this->page('laravelusers::modern.settings', $data);
        $toast = collect($page['data']['packages']['choices'])->firstWhere('package', 'toast');
        $this->assertTrue($toast['setup_completed']);
        $this->assertNull($toast['configure_name']);
        $this->assertArrayNotHasKey('package-toast-configure', $page['forms']);
        $this->assertNotContains('package-toast-configure', array_column($page['data']['settings_actions'], 'name'));
        $this->assertSame('remove', $page['forms']['package-toast']['values']['operation']);
        $this->assertArrayHasKey('package-laravel-roles-configure', $page['forms']);

        $data['packageQueueReady'] = false;
        $page = $this->page('laravelusers::modern.settings', $data);
        $this->assertTrue($page['forms']['package-toast']['disabled']);
        $this->assertTrue($page['forms']['package-laravel-roles-configure']['disabled']);
    }

    private function page(string $view, array $data): array
    {
        return $this->app->make(NativePageData::class)->forView($view, $data, $this->request());
    }

    private function request(): Request
    {
        $request = Request::create('/users');
        $request->setLaravelSession($this->app['session']->driver());
        $request->setUserResolver(fn () => $this->app['auth']->user());

        return $request;
    }
}
