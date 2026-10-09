<?php

namespace jeremykenedy\laravelusers\Test\Feature;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use jeremykenedy\laravelusers\Support\AppearancePreferences;
use jeremykenedy\laravelusers\Test\Fixtures\SoftUser;
use jeremykenedy\laravelusers\Test\Fixtures\User;
use jeremykenedy\laravelusers\Test\TestCase;

class AppearancePreferencesTest extends TestCase
{
    private function enable(): void
    {
        (require dirname(__DIR__, 2).'/src/database/appearance/2026_10_08_150344_create_laravelusers_appearance_preferences_table.php')->up();
        (require dirname(__DIR__, 2).'/src/database/appearance/2026_10_08_153657_add_gradient_strength_to_laravelusers_appearance_preferences_table.php')->up();
        (require dirname(__DIR__, 2).'/src/database/appearance/2026_10_08_164316_add_dark_appearance_to_laravelusers_appearance_preferences_table.php')->up();
        config(['laravelusers.appearance.per_user' => true, 'laravelusers.avatar.enabled' => true]);
    }

    public function test_existing_apps_do_not_need_a_migration_and_ignore_unrequested_appearance_fields(): void
    {
        $user = $this->user();
        $columns = Schema::getColumnListing('users');
        $this->actingAs($user)->get('/users/'.$user->id.'/edit')->assertOk()->assertDontSee('<input id="user-card-color"', false);
        $this->put('/users/'.$user->id, ['name' => $user->name, 'email' => $user->email, 'user_card_color' => '#123456'])->assertSessionHasNoErrors();
        $this->assertFalse(Schema::hasTable('laravelusers_appearance_preferences'));
        $this->assertSame($columns, Schema::getColumnListing('users'));
        config(['laravelusers.appearance.per_user' => true]);
        $this->get('/users/'.$user->id.'/edit')->assertOk()->assertSee('optional appearance preferences migration');
        $this->put('/users/'.$user->id, ['name' => $user->name, 'email' => $user->email])->assertSessionHasNoErrors();
        $this->put('/users/'.$user->id, ['name' => 'Changed', 'email' => $user->email, 'user_card_color' => '#123456', 'user_card_gradient' => 'off'])->assertSessionHasErrors('user_card_color');
        $this->assertSame($user->name, $user->fresh()->name);
    }

    public function test_individual_colors_and_gradients_survive_global_changes_and_can_return_to_inheritance(): void
    {
        $this->enable();
        $actor = $this->user();
        $data = ['name' => 'AppearanceUser', 'email' => 'appearance@example.com', 'password' => 'password', 'password_confirmation' => 'password', 'user_card_color' => '#264e36', 'user_card_gradient' => 'off'];
        $this->actingAs($actor)->post('/users', $data)->assertSessionHasNoErrors();
        $user = User::where('name', $data['name'])->firstOrFail();
        config(['laravelusers.profileCardColor' => '#2458b7', 'laravelusers.profileCardGradient' => true]);
        foreach (['bootstrap4', 'bootstrap5', 'tailwind'] as $framework) {
            config(['laravelusers.frontend' => $framework]);
            $this->get('/users')->assertOk()->assertSee('--lu-profile-color: #264e36', false)->assertSee('--lu-profile-image: none', false);
            $this->get('/users/'.$user->id)->assertOk()->assertSee('--lu-profile-color: #264e36', false);
            $this->get('/users/'.$user->id.'/edit')->assertOk()->assertSee('name="user_card_color"', false);
            $this->get('/users/create')->assertOk()->assertSee('name="user_card_gradient"', false);
        }
        $this->postJson('/search-users', ['user_search_box' => $user->name, 'include_avatar' => 1])->assertOk()->assertJsonPath('appearance.'.$user->id.'.base', '#264e36')->assertJsonPath('appearance.'.$user->id.'.gradient', false);
        $this->put('/users/'.$user->id, ['name' => $user->name, 'email' => $user->email])->assertSessionHasNoErrors();
        $this->assertSame(['color' => '#264e36', 'gradient' => false], AppearancePreferences::listing([$user])[$user->id]);
        $this->put('/users/'.$user->id, ['name' => $user->name, 'email' => $user->email, 'user_card_color' => '', 'user_card_gradient' => 'inherit'])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('laravelusers_appearance_preferences', 0);
        $this->get('/users/'.$user->id)->assertOk()->assertSee('--lu-profile-color: #2458b7', false);
    }

    public function test_gradient_strength_is_validated_persisted_and_can_inherit_the_global_default(): void
    {
        $this->enable();
        $user = $this->user();
        $this->actingAs($user);
        $data = ['name' => $user->name, 'email' => $user->email];
        foreach ([-1, 101, 'x', ['50']] as $strength) {
            $this->put('/users/'.$user->id, $data + ['user_card_gradient_strength' => $strength])->assertSessionHasErrors('user_card_gradient_strength');
        }
        $this->put('/users/'.$user->id, $data + ['user_card_gradient_strength' => 0])->assertSessionHasNoErrors();
        $this->assertSame(0, AppearancePreferences::listing([$user])[$user->id]['strength']);
        $this->assertSame('#ffffff00', AppearancePreferences::colors([$user])[$user->id]['highlight']);
        config(['laravelusers.profileCardGradientStrength' => 100]);
        $this->assertSame(0, AppearancePreferences::colors([$user])[$user->id]['strength']);
        $this->put('/users/'.$user->id, $data + ['user_card_gradient_strength' => ''])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('laravelusers_appearance_preferences', 0);
        config(['laravelusers.access.edit_user_appearance.mode' => 'deny', 'laravelusers.settings.enabled' => true]);
        $this->put('/users/'.$user->id, $data + ['user_card_gradient_strength' => 50])->assertSessionHasErrors('user_card_gradient_strength');
    }

    public function test_invalid_and_unauthorized_preferences_leave_user_and_settings_unchanged(): void
    {
        $this->enable();
        $user = $this->user();
        $this->actingAs($user);
        foreach (['red; background:url(https://example.com)', '#123', ['#123456']] as $color) {
            $this->put('/users/'.$user->id, ['name' => 'Changed', 'email' => $user->email, 'user_card_color' => $color])->assertSessionHasErrors('user_card_color');
        }
        $this->put('/users/'.$user->id, ['name' => $user->name, 'email' => $user->email, 'user_card_gradient' => 'invalid'])->assertSessionHasErrors('user_card_gradient');
        config(['laravelusers.settings.enabled' => true, 'laravelusers.access.edit_user_appearance.mode' => 'deny']);
        $this->get('/users/'.$user->id.'/edit')->assertOk()->assertDontSee('<input id="user-card-color"', false);
        $this->put('/users/'.$user->id, ['name' => 'Changed', 'email' => $user->email, 'user_card_color' => '#123456'])->assertSessionHasErrors('user_card_color');
        $this->put('/users/'.$user->id, ['name' => $user->name, 'email' => $user->email])->assertSessionHasNoErrors();
        $this->assertSame($user->name, $user->fresh()->name);
        $this->assertDatabaseCount('laravelusers_appearance_preferences', 0);
    }

    public function test_dark_preferences_are_independent_preserved_when_omitted_and_can_inherit_again(): void
    {
        $this->enable();
        $actor = $this->user();
        $data = ['name' => 'DarkAppearance', 'email' => 'dark-appearance@example.com', 'password' => 'password', 'password_confirmation' => 'password', 'user_card_color' => '#264e36', 'user_card_gradient' => 'on', 'user_card_gradient_strength' => 80, 'user_card_dark_color' => '#19283a', 'user_card_dark_gradient' => 'off', 'user_card_dark_gradient_strength' => 0];
        $this->actingAs($actor)->post('/users', $data)->assertSessionHasNoErrors();
        $user = User::where('email', $data['email'])->firstOrFail();
        config(['laravelusers.profileCardDarkColor' => '#503260', 'laravelusers.profileCardDarkGradient' => true, 'laravelusers.profileCardDarkGradientStrength' => 100]);
        $colors = AppearancePreferences::colors([$user])[$user->id];
        $this->assertSame('#264e36', $colors['base']);
        $this->assertSame(80, $colors['strength']);
        $this->assertSame('#19283a', $colors['dark']['base']);
        $this->assertFalse($colors['dark']['gradient']);
        $this->assertSame(0, $colors['dark']['strength']);
        foreach (['bootstrap4', 'bootstrap5', 'tailwind'] as $framework) {
            config(['laravelusers.frontend' => $framework]);
            $this->get('/users')->assertOk()->assertSee('--lu-profile-dark-color: #19283a', false);
            $this->get('/users/'.$user->id)->assertOk()->assertSee('--lu-profile-dark-image: none', false);
            $this->get('/users/'.$user->id.'/edit')->assertOk()->assertSee('name="user_card_dark_color"', false);
            $this->get('/users/create')->assertOk()->assertSee('name="user_card_dark_gradient_strength"', false);
        }
        $this->postJson('/search-users', ['user_search_box' => $user->name, 'include_avatar' => 1])->assertOk()->assertJsonPath('appearance.'.$user->id.'.dark.base', '#19283a');
        $update = ['name' => $user->name, 'email' => $user->email];
        $this->put('/users/'.$user->id, $update)->assertSessionHasNoErrors();
        $this->assertSame('#19283a', AppearancePreferences::listing([$user])[$user->id]['dark_color']);
        $this->put('/users/'.$user->id, $update + ['user_card_dark_color' => '', 'user_card_dark_gradient' => 'inherit', 'user_card_dark_gradient_strength' => ''])->assertSessionHasNoErrors();
        $colors = AppearancePreferences::colors([$user])[$user->id];
        $this->assertSame('#264e36', $colors['base']);
        $this->assertSame('#503260', $colors['dark']['base']);
        $this->assertSame(100, $colors['dark']['strength']);
        $this->assertTrue($colors['dark']['gradient']);
    }

    public function test_dark_validation_uses_existing_appearance_authorization(): void
    {
        $this->enable();
        $user = $this->user();
        $this->actingAs($user);
        $data = ['name' => 'Changed', 'email' => $user->email];
        foreach (['user_card_dark_color' => '#fff; color:red', 'user_card_dark_gradient' => 'invalid', 'user_card_dark_gradient_strength' => 101] as $field => $value) {
            $this->put('/users/'.$user->id, $data + [$field => $value])->assertSessionHasErrors($field);
        }
        config(['laravelusers.settings.enabled' => true, 'laravelusers.access.edit_user_appearance.mode' => 'deny']);
        $this->put('/users/'.$user->id, $data + ['user_card_dark_color' => '#19283a'])->assertSessionHasErrors('user_card_dark_color');
        $this->assertSame($user->name, $user->fresh()->name);
        $this->assertDatabaseCount('laravelusers_appearance_preferences', 0);
    }

    public function test_dark_migration_preserves_existing_preferences_and_older_tables_still_work(): void
    {
        $this->enable();
        $migration = require dirname(__DIR__, 2).'/src/database/appearance/2026_10_08_164316_add_dark_appearance_to_laravelusers_appearance_preferences_table.php';
        $migration->down();
        $user = $this->user();
        $columns = Schema::getColumnListing('users');
        AppearancePreferences::save($user, ['user_card_color' => '#264e36', 'user_card_gradient' => 'off', 'user_card_gradient_strength' => 70]);
        $this->actingAs($user)->get('/users/'.$user->id.'/edit')->assertOk()->assertDontSee('name="user_card_dark_color"', false);
        $this->put('/users/'.$user->id, ['name' => 'Changed', 'email' => $user->email, 'user_card_dark_color' => '#19283a'])->assertSessionHasErrors('user_card_dark_color');
        $this->assertSame('#264e36', AppearancePreferences::colors([$user])[$user->id]['dark']['base']);
        $migration->up();
        $this->assertSame($columns, Schema::getColumnListing('users'));
        $this->assertSame(['color' => '#264e36', 'gradient' => false, 'strength' => 70], AppearancePreferences::listing([$user])[$user->id]);
        AppearancePreferences::save($user, ['user_card_dark_color' => '#19283a']);
        $migration->down();
        $this->get('/users/'.$user->id)->assertOk()->assertSee('--lu-profile-color: #264e36', false);
        $this->assertSame('#264e36', AppearancePreferences::colors([$user])[$user->id]['dark']['base']);
        $migration->up();
        foreach (['bootstrap4', 'bootstrap5', 'tailwind'] as $framework) {
            config(['laravelusers.frontend' => $framework]);
            $this->get('/users/'.$user->id.'/edit')->assertOk()->assertSee('id="user-card-dark-color" name="user_card_dark_color" type="color" value="#264e36"', false);
        }
    }

    public function test_older_preference_tables_work_with_strict_attribute_access(): void
    {
        $this->enable();
        (require dirname(__DIR__, 2).'/src/database/appearance/2026_10_08_164316_add_dark_appearance_to_laravelusers_appearance_preferences_table.php')->down();
        $user = $this->user();
        AppearancePreferences::save($user, ['user_card_color' => '#264e36']);
        if (method_exists(Model::class, 'preventAccessingMissingAttributes')) {
            Model::preventAccessingMissingAttributes();
        }

        try {
            $this->assertSame('#264e36', AppearancePreferences::colors([$user])[$user->id]['dark']['base']);
            $this->assertSame(['color' => '#264e36', 'gradient' => null], AppearancePreferences::listing([$user])[$user->id]);
        } finally {
            if (method_exists(Model::class, 'preventAccessingMissingAttributes')) {
                Model::preventAccessingMissingAttributes(false);
            }
        }
    }

    public function test_soft_delete_keeps_preferences_and_permanent_delete_cleans_them_up(): void
    {
        $this->enable();
        Schema::table('users', fn (Blueprint $table) => $table->softDeletes());
        config(['laravelusers.defaultUserModel' => SoftUser::class]);
        $user = SoftUser::findOrFail($this->user()->id);
        AppearancePreferences::save($user, ['user_card_color' => '#264e36', 'user_card_gradient' => 'on']);
        $user->delete();
        $this->assertDatabaseCount('laravelusers_appearance_preferences', 1);
        $user->restore();
        $this->assertSame(true, AppearancePreferences::listing([$user])[$user->id]['gradient']);
        $user->delete();
        $user->forceDelete();
        $this->assertDatabaseCount('laravelusers_appearance_preferences', 0);
    }
}
