<?php

namespace jeremykenedy\laravelusers\Test\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use jeremykenedy\laravelusers\Models\UserSetting;
use jeremykenedy\laravelusers\Test\Fixtures\SoftUser;
use jeremykenedy\laravelusers\Test\TestCase;

class SettingsTest extends TestCase
{
    private function defaults(): array
    {
        return ['avatar_source' => 'ui-avatars', 'profile_color' => '#264e36', 'edit_color' => '#705000', 'profile_gradient' => 0];
    }

    private function enable(): void
    {
        config(['laravelusers.settings.enabled' => true]);
        (require dirname(__DIR__, 2).'/src/database/settings/2026_10_08_145311_create_laravelusers_settings_table.php')->up();
        Gate::define('manage-laravelusers-settings', fn ($actor) => $actor->id === 1);
    }

    public function test_settings_are_opt_in_and_require_an_explicit_settings_gate(): void
    {
        $actor = $this->user();
        $this->actingAs($actor)->get('/users/settings')->assertNotFound();
        $this->put('/users/settings', $this->defaults())->assertNotFound();
        $this->get('/users')->assertOk()->assertDontSee('class="lu-settings-button', false);
        config(['laravelusers.settings.enabled' => true]);
        $this->get('/users/settings')->assertForbidden();
        $this->put('/users/settings', $this->defaults())->assertForbidden();
        $this->assertFalse(Schema::hasTable('laravelusers_settings'));
    }

    public function test_missing_migration_keeps_pages_working_and_rejects_settings_writes(): void
    {
        config(['laravelusers.settings.enabled' => true]);
        Gate::define('manage-laravelusers-settings', fn () => true);
        $this->actingAs($this->user())->get('/users/settings')->assertOk()->assertSee('optional user settings migration');
        $this->get('/users')->assertOk();
        $this->put('/users/settings', $this->defaults())->assertStatus(409);
    }

    public function test_global_settings_persist_across_pages_without_changing_unrelated_config(): void
    {
        $this->enable();
        config(['laravelusers.custom' => 'kept']);
        $actor = $this->user();
        $this->actingAs($actor);
        foreach (['bootstrap4', 'bootstrap5', 'tailwind'] as $framework) {
            config(['laravelusers.frontend' => $framework]);
            $this->get('/users/settings')->assertOk()->assertSee('View user card color')->assertDontSee('name="access[', false);
            $this->get('/users')->assertOk()->assertSee(route('users.settings'), false);
        }
        $this->from('/users/settings')->put('/users/settings', $this->defaults())->assertRedirect('/users/settings')->assertSessionHasNoErrors();
        $this->get('/users/'.$actor->id)->assertOk()->assertSee('--lu-profile-color: #264e36', false);
        $this->assertSame('ui-avatars', config('laravelusers.avatar.source'));
        $this->assertEquals(false, config('laravelusers.profileCardGradient'));
        $this->assertSame('kept', config('laravelusers.custom'));
        $this->assertDatabaseCount('laravelusers_settings', 1);
        $other = $this->user();
        $this->actingAs($other)->get('/users/settings')->assertForbidden();
        $this->get('/users')->assertOk()->assertDontSee(route('users.settings'), false);
    }

    public function test_global_welcome_email_setting_controls_creation_and_email_actions(): void
    {
        $this->enable();
        config(['laravelusers.emails.enabled' => true, 'laravelusers.emails.welcome' => true, 'laravelusers.bulkActions' => true]);
        $actor = $this->user();
        $this->actingAs($actor);

        $this->get('/users/create')->assertOk()->assertDontSee('name="send_welcome_email"', false);
        $this->put('/users/settings/emails', [
            'welcome_enabled'   => 1,
            'goodbye'           => 0,
            'goodbye_on_delete' => 0,
            'templates'         => ['welcome' => ['subject' => 'Welcome to the team', 'message' => 'Your account is ready.']],
        ])->assertRedirect(route('users.settings').'#emails')->assertSessionHasNoErrors();

        $this->get('/users/create')->assertOk()->assertSee('name="send_welcome_email"', false);
        $this->get('/users')->assertOk()->assertSee('<option value="welcome">', false);
        $this->assertSame('Welcome to the team', config('laravelusers.emails.welcome_subject'));
        $this->assertTrue(config('laravelusers.welcome.enabled'));
    }

    public function test_invalid_values_access_changes_without_a_roles_package_and_lockout_are_rejected(): void
    {
        $this->enable();
        $this->actingAs($this->user());
        foreach (['profile_color' => 'url(https://example.com)', 'avatar_source' => 'unknown', 'notifications_driver' => 'toast', 'profile_gradient_strength' => 101, 'edit_gradient_strength' => -1, 'edit_gradient' => 'invalid', 'access' => ['edit_settings' => ['mode' => 'deny']]] as $field => $value) {
            $this->put('/users/settings', array_replace($this->defaults(), [$field => $value]))->assertSessionHasErrors($field);
        }
        $this->assertDatabaseCount('laravelusers_settings', 0);
        config(['laravelusers.access.edit_appearance.mode' => 'deny']);
        $this->put('/users/settings', $this->defaults())->assertSessionHasErrors('profile_color');
    }

    public function test_dark_global_settings_persist_without_changing_light_settings_and_keep_the_same_permissions(): void
    {
        $this->enable();
        $actor = $this->user();
        $this->actingAs($actor);
        $dark = ['profile_dark_color' => '#19283a', 'profile_dark_gradient' => 1, 'profile_dark_gradient_strength' => 75, 'edit_dark_color' => '#49340e', 'edit_dark_gradient' => 0, 'edit_dark_gradient_strength' => 0];
        $this->put('/users/settings', $this->defaults() + $dark)->assertSessionHasNoErrors();
        $this->get('/users/'.$actor->id)->assertOk()->assertSee('--lu-profile-dark-color: #19283a', false)->assertSee('--lu-profile-color: #264e36', false);
        $this->assertTrue(config('laravelusers.profileCardDarkGradient'));
        $this->assertFalse(config('laravelusers.editCardDarkGradient'));
        $this->assertSame(0, config('laravelusers.editCardDarkGradientStrength'));
        $this->put('/users/settings', $this->defaults())->assertSessionHasNoErrors();
        $this->assertSame('#19283a', UserSetting::findOrFail('global')->value['profileCardDarkColor']);
        foreach (['profile_dark_color' => 'url(x)', 'profile_dark_gradient' => 'yes', 'profile_dark_gradient_strength' => 101, 'edit_dark_color' => '#12', 'edit_dark_gradient_strength' => -1] as $field => $value) {
            $this->put('/users/settings', $this->defaults() + [$field => $value])->assertSessionHasErrors($field);
        }
        UserSetting::findOrFail('global')->update(['value' => ['access' => ['edit_appearance' => ['mode' => 'deny']]]]);
        $this->put('/users/settings', $dark)->assertSessionHasErrors('profile_dark_color');
    }

    public function test_settings_writes_have_an_independent_rate_limit(): void
    {
        $this->enable();
        $actor = $this->user();
        $this->actingAs($actor);
        $key = 'laravelusers-settings-write'.sha1((string) $actor->id);
        for ($attempt = 0; $attempt < 10; $attempt++) {
            RateLimiter::hit($key, 60);
        }
        $this->put('/users/settings', $this->defaults())->assertStatus(429);
        $this->get('/users/settings')->assertOk();
        RateLimiter::clear($key);
        RateLimiter::hit(sha1((string) $actor->id), 60);
        $this->put('/users/settings', $this->defaults())->assertSessionHasNoErrors()->assertRedirect();
    }

    public function test_access_rules_protect_routes_search_bulk_and_email_preview_and_hide_actions(): void
    {
        $this->enable();
        Notification::fake();
        Schema::table('users', fn (Blueprint $table) => $table->softDeletes());
        config(['laravelusers.defaultUserModel' => SoftUser::class, 'laravelusers.softDeletedEnabled' => true, 'laravelusers.bulkActions' => true]);
        $actor = $this->user();
        $recipient = SoftUser::findOrFail($this->user()->id);
        $this->actingAs($actor);
        $rules = [];
        foreach (['create_users', 'edit_users', 'delete_users', 'view_deleted', 'edit_deleted', 'restore_users', 'force_delete', 'email_message', 'email_reset', 'email_welcome', 'email_deleted'] as $action) {
            $rules[$action] = ['mode' => 'deny'];
        }
        UserSetting::create(['key' => 'global', 'value' => ['access' => $rules]]);
        foreach (['bootstrap4', 'bootstrap5', 'tailwind'] as $framework) {
            config(['laravelusers.frontend' => $framework]);
            $this->get('/users')->assertOk()->assertDontSee('href="'.route('users.create').'"', false)->assertDontSee('data-lu-email-action=', false)->assertDontSee('aria-label="Select '.$recipient->name.'"', false);
        }
        $this->get('/users/create')->assertForbidden();
        $this->post('/users', [])->assertForbidden();
        $this->get('/users/'.$recipient->id.'/edit')->assertForbidden();
        $this->put('/users/'.$recipient->id, [])->assertForbidden();
        $this->delete('/users/'.$recipient->id)->assertForbidden();
        $this->post('/users/bulk', ['action' => 'delete', 'ids' => [$recipient->id]])->assertForbidden();
        foreach (['message', 'reset', 'welcome'] as $action) {
            $this->post('/users/email', ['action' => $action, 'ids' => [$recipient->id]])->assertForbidden();
            $this->post('/users/email/preview', ['action' => $action, 'ids' => [$recipient->id]])->assertForbidden();
        }
        $this->get('/users/deleted')->assertForbidden();
        $recipient->delete();
        $this->get('/users/deleted/'.$recipient->id.'/edit')->assertForbidden();
        $this->put('/users/deleted/'.$recipient->id, [])->assertForbidden();
        $this->post('/users/'.$recipient->id.'/restore')->assertForbidden();
        $this->delete('/users/'.$recipient->id.'/force')->assertForbidden();
        $this->post('/users/email/preview', ['action' => 'message', 'deleted' => 1, 'ids' => [$recipient->id]])->assertForbidden();
        $this->assertTrue($recipient->fresh()->trashed());
        Notification::assertNothingSent();
        UserSetting::findOrFail('global')->update(['value' => ['access' => ['view_users' => ['mode' => 'deny']]]]);
        $this->get('/users')->assertForbidden();
        $this->get('/users/'.$actor->id)->assertForbidden();
        $this->postJson('/search-users', ['user_search_box' => $actor->name])->assertForbidden();
    }

    public function test_deleted_users_can_be_edited_only_through_the_deleted_routes_when_enabled(): void
    {
        $this->enable();
        Schema::table('users', fn (Blueprint $table) => $table->softDeletes());
        config(['laravelusers.defaultUserModel' => SoftUser::class, 'laravelusers.softDeletedEnabled' => true]);
        $actor = $this->user();
        $recipient = SoftUser::findOrFail($this->user()->id);
        $recipient->delete();
        $this->actingAs($actor);
        foreach (['bootstrap4', 'bootstrap5', 'tailwind'] as $framework) {
            config(['laravelusers.frontend' => $framework]);
            $this->get('/users/deleted')->assertOk()->assertSee(route('users.deleted.edit', $recipient->id), false);
            $this->get('/users/deleted/'.$recipient->id.'/edit')->assertOk()->assertSee('action="'.route('users.deleted.update', $recipient->id).'"', false);
        }
        $this->get('/users/'.$recipient->id.'/edit')->assertNotFound();
        $this->get('/users/deleted/'.$actor->id.'/edit')->assertNotFound();
        $this->put('/users/deleted/'.$recipient->id, ['name' => 'EditedDeletedUser', 'email' => $recipient->email])->assertSessionHasNoErrors();
        $this->assertSame('EditedDeletedUser', $recipient->fresh()->name);
        $this->assertTrue($recipient->fresh()->trashed());
    }
}
