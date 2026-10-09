<?php

namespace jeremykenedy\laravelusers\Test\Feature;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use InvalidArgumentException;
use jeremykenedy\laravelusers\Support\NativePageData;
use jeremykenedy\laravelusers\Test\TestCase;

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
