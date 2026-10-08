<?php

namespace jeremykenedy\laravelusers\Test\Feature;

use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use jeremykenedy\laravelusers\Models\EmailChange;
use jeremykenedy\laravelusers\Notifications\ConfirmEmailChange;
use jeremykenedy\laravelusers\Support\AccountPreferences;
use jeremykenedy\laravelusers\Support\AppearancePreferences;
use jeremykenedy\laravelusers\Support\AvatarPreferences;
use jeremykenedy\laravelusers\Test\TestCase;

class AccountSettingsTest extends TestCase
{
    private function install(): void
    {
        foreach (['accounts', 'avatar', 'appearance', 'settings'] as $feature) {
            foreach (glob(dirname(__DIR__, 2).'/src/database/'.$feature.'/*.php') as $path) {
                (require $path)->up();
            }
        }
        Gate::define('manage-laravelusers-settings', fn ($user) => $user->id === 1);
    }

    private function enable(): void
    {
        $this->install();
        config(['laravelusers.account.enabled' => true, 'laravelusers.account.settings_enabled' => true]);
    }

    private function emailRequest(string $email): array
    {
        return ['section' => 'email', 'email' => $email, 'current_password' => 'password'];
    }

    private function links(): array
    {
        $urls = [];
        foreach (Notification::sent(new AnonymousNotifiable(), ConfirmEmailChange::class) as $notification) {
            $urls[$notification->side] = $notification->url;
        }

        return $urls;
    }

    public function test_accounts_are_opt_in_require_authentication_and_do_not_grant_admin_access(): void
    {
        $user = $this->user();
        $this->get('/users/account')->assertRedirect('/login');
        $this->actingAs($user)->get('/users/account')->assertNotFound();
        $this->install();
        $this->get('/users/account')->assertNotFound();
        $this->assertFalse(AvatarPreferences::available($user));
        $this->assertFalse(AppearancePreferences::available($user));
        config(['laravelusers.account.enabled' => true]);
        $this->get('/users/account')->assertOk()->assertSee('My account')->assertDontSee('name="username"', false);
        $this->putJson('/users/account', ['section' => 'profile'])->assertForbidden();
        config(['laravelusers.account.settings_enabled' => true, 'laravelusers.settings.enabled' => true, 'laravelusers.access.view_users.mode' => 'deny', 'laravelusers.access.edit_users.mode' => 'deny']);
        $this->get('/users')->assertForbidden();
        $this->get('/users/account')->assertOk()->assertSee('name="username"', false);
    }

    public function test_individual_overrides_inherit_and_applying_one_default_preserves_other_overrides(): void
    {
        $this->install();
        config(['laravelusers.settings.enabled' => true]);
        $actor = $this->user();
        $other = $this->user();
        AccountPreferences::save($other, ['account_enabled' => 'on', 'account_settings_enabled' => 'on']);
        $this->assertTrue(AccountPreferences::editable($other));
        $this->assertFalse(AccountPreferences::enabled($actor));
        $this->actingAs($actor);
        $data = ['enabled' => 0, 'settings_enabled' => 0, 'apply_all' => 'enabled'];
        $this->putJson('/users/settings/accounts', $data)->assertUnprocessable()->assertJsonValidationErrors('confirmation');
        $this->assertTrue(AccountPreferences::editable($other));
        $this->put('/users/settings/accounts', $data + ['confirmation' => 'change'])->assertRedirect();
        $this->get('/users/settings')->assertOk();
        $this->assertFalse(AccountPreferences::enabled($other));
        $this->assertTrue(AccountPreferences::enabled($other, 'settings_enabled'));
        config(['laravelusers.account.enabled' => true]);
        $this->assertTrue(AccountPreferences::enabled($other));
        AccountPreferences::save($other, ['account_enabled' => 'off']);
        $this->assertFalse(AccountPreferences::enabled($other));
        AccountPreferences::save($other, ['account_enabled' => 'inherit']);
        $this->assertTrue(AccountPreferences::enabled($other));
        $this->actingAs($other)->putJson('/users/settings/accounts', $data + ['confirmation' => 'change'])->assertForbidden();
    }

    public function test_profile_changes_target_only_the_signed_in_user_and_ignore_privileged_fields(): void
    {
        $this->enable();
        $user = $this->user();
        $other = $this->user();
        $this->actingAs($user);
        $this->put('/users/account', ['section' => 'profile', 'username' => 'casey', 'full_name' => 'Casey Rivers', 'id' => $other->id, 'role' => 'admin', 'account_enabled' => 'off', 'email' => 'bypass@example.com'])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('casey', $user->fresh()->name);
        $this->assertSame('Casey Rivers', AccountPreferences::fullName($user));
        $this->assertSame($user->email, $user->fresh()->email);
        $this->assertSame($other->name, $other->fresh()->name);
        $this->assertTrue(AccountPreferences::enabled($user));
        $this->putJson('/users/account', ['section' => 'profile', 'username' => $other->name, 'full_name' => '<img src=x onerror=alert(1)>'])->assertUnprocessable()->assertJsonValidationErrors(['username', 'full_name']);
    }

    public function test_users_can_change_appearance_without_admin_appearance_permissions(): void
    {
        $this->enable();
        config(['laravelusers.settings.enabled' => true, 'laravelusers.access.edit_user_appearance.mode' => 'deny']);
        $user = $this->user();
        $this->actingAs($user)->put('/users/account', ['section' => 'appearance', 'avatar_source' => 'ui-avatars', 'user_card_color' => '#204070', 'user_card_gradient' => 'on', 'user_card_gradient_strength' => 60, 'user_card_dark_color' => '#101820', 'user_card_dark_gradient' => 'off'])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('ui-avatars', AvatarPreferences::listing([$user])[$user->id]);
        $this->get('/users/account')->assertOk()->assertSee('--lu-profile-color: #204070', false)->assertSee('--lu-profile-dark-color: #101820', false);
        config(['laravelusers.account.appearance' => false]);
        $this->putJson('/users/account', ['section' => 'appearance', 'user_card_color' => '#abcdef'])->assertUnprocessable()->assertJsonValidationErrors('user_card_color');
        $this->put('/users/account', ['section' => 'appearance', 'avatar_source' => 'initials'])->assertRedirect();
    }

    public function test_password_changes_require_current_password_confirmation_and_configured_rules(): void
    {
        $this->enable();
        $user = $this->user();
        $this->actingAs($user);
        $data = ['section' => 'password', 'current_password' => 'wrong', 'password' => 'replacement123', 'password_confirmation' => 'replacement123'];
        $this->putJson('/users/account', $data)->assertUnprocessable()->assertJsonValidationErrors('current_password');
        $this->putJson('/users/account', array_replace($data, ['current_password' => 'password', 'password_confirmation' => 'wrong']))->assertUnprocessable()->assertJsonValidationErrors('password');
        $this->put('/users/account', array_replace($data, ['current_password' => 'password']))->assertRedirect()->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check('replacement123', $user->fresh()->password));
        $this->assertNotNull($user->fresh()->remember_token);
        config(['laravelusers.account.password' => false]);
        $this->putJson('/users/account', $data)->assertForbidden();
    }

    public function test_email_requires_both_addresses_and_links_are_encrypted_single_use_and_bound_to_the_user(): void
    {
        $this->enable();
        Notification::fake();
        $user = $this->user();
        $other = $this->user();
        $this->actingAs($user)->put('/users/account', $this->emailRequest('new@example.com'))->assertRedirect()->assertSessionHasNoErrors();
        $this->assertCount(2, Notification::sent(new AnonymousNotifiable(), ConfirmEmailChange::class));
        $this->assertSame($user->email, $user->fresh()->email);
        $record = EmailChange::firstOrFail();
        $this->assertStringNotContainsString('new@example.com', $record->getRawOriginal('new_email'));
        $links = $this->links();
        $this->actingAs($other)->post($links['old'])->assertStatus(410);
        $this->actingAs($user)->get($links['old'])->assertOk();
        $this->assertNull($record->fresh()->old_confirmed_at);
        $this->post($links['old'])->assertOk()->assertSee('Confirm the other email');
        $this->assertSame($user->email, $user->fresh()->email);
        $this->post($links['old'])->assertStatus(410);
        $this->post($links['new'])->assertOk()->assertSee('Your email address has been changed');
        $this->assertSame('new@example.com', $user->fresh()->email);
        $this->assertDatabaseCount('laravelusers_email_changes', 0);
        $this->post($links['new'])->assertStatus(410);
        $this->post('/users/account/email/'.str_repeat('x', 5000))->assertStatus(410);
    }

    public function test_email_changes_expire_and_new_requests_password_changes_and_admin_email_changes_invalidate_old_links(): void
    {
        $this->enable();
        Notification::fake();
        $user = $this->user();
        $this->actingAs($user)->put('/users/account', $this->emailRequest('new@example.com'))->assertRedirect();
        $links = $this->links();
        Carbon::setTestNow(now()->addMinutes(1440));

        try {
            $this->post($links['old'])->assertStatus(410);
        } finally {
            Carbon::setTestNow();
        }
        Notification::fake();
        $this->put('/users/account', $this->emailRequest('different@example.com'))->assertRedirect();
        $this->post($links['old'])->assertStatus(410);
        $new = $this->links();
        $user->email = 'admin-changed@example.com';
        $user->save();
        $this->post($new['old'])->assertStatus(410);
        $this->assertSame('admin-changed@example.com', $user->fresh()->email);
    }

    public function test_account_deletion_requires_exact_confirmation_and_current_password_then_logs_out(): void
    {
        $this->enable();
        $user = $this->user();
        $other = $this->user();
        $this->actingAs($user)->deleteJson('/users/account', ['confirmation' => 'yes', 'current_password' => 'password'])->assertUnprocessable();
        $this->deleteJson('/users/account', ['confirmation' => 'delete', 'current_password' => 'wrong'])->assertUnprocessable();
        $this->assertNotNull($user->fresh());
        $this->delete('/users/account', ['confirmation' => 'delete', 'current_password' => 'password', 'id' => $other->id])->assertRedirect('/');
        $this->assertNull($user->fresh());
        $this->assertNotNull($other->fresh());
        $this->assertGuest();
    }
}
