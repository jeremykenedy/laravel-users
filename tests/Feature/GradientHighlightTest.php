<?php

namespace jeremykenedy\laravelusers\Test\Feature;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ViewErrorBag;
use jeremykenedy\laravelusers\Models\UserSetting;
use jeremykenedy\laravelusers\Support\AppearancePreferences;
use jeremykenedy\laravelusers\Support\Frontend;
use jeremykenedy\laravelusers\Test\TestCase;

class GradientHighlightTest extends TestCase
{
    private function enableAppearance(array $excludedMigrations = []): void
    {
        foreach (glob(dirname(__DIR__, 2).'/src/database/appearance/*.php') as $path) {
            if (!in_array(basename($path), $excludedMigrations, true)) {
                (require $path)->up();
            }
        }
        config(['laravelusers.appearance.per_user' => true]);
    }

    private function enableSettings(): void
    {
        config(['laravelusers.settings.enabled' => true]);
        (require dirname(__DIR__, 2).'/src/database/settings/2026_10_08_145311_create_laravelusers_settings_table.php')->up();
        Gate::define('manage-laravelusers-settings', fn () => true);
    }

    public function test_the_existing_white_highlight_and_strength_are_unchanged_by_default(): void
    {
        $base = Frontend::colors('#2458b7');
        foreach ([0 => '#ffffff00', 50 => '#ffffff48', 100 => '#ffffff90'] as $strength => $highlight) {
            $colors = Frontend::gradientColors($base, $strength);
            $this->assertSame($highlight, $colors['highlight']);
            $this->assertSame('#2458b7', $colors['base']);
            $this->assertSame('#fff', $colors['text']);
            $this->assertSame($colors, Frontend::gradientColors($base, $strength, '#ffffff'));
        }
        $this->assertSame('#ffffff48', Frontend::gradientColors($base, 50, 'red; color:blue')['highlight']);
        $this->assertSame('#aabbcc48', Frontend::gradientColors($base, 50, '#abc')['highlight']);
    }

    public function test_existing_custom_forms_can_use_the_original_appearance_partial_arguments(): void
    {
        $keys = ['color' => 'profileCardColor', 'gradient' => 'profileCardGradient', 'strength' => 'profileCardGradientStrength'];
        $html = view('laravelusers::partials.settings-appearance', ['kind' => 'profile', 'keys' => $keys, 'fallback' => $keys, 'colorLabel' => 'Profile card color', 'errors' => new ViewErrorBag()])->render();
        $this->assertStringContainsString('name="profile_color"', $html);
        $this->assertStringContainsString('name="profile_gradient_highlight_color"', $html);
        $this->assertStringContainsString('type="color" value="#ffffff" data-lu-gradient-highlight-color', $html);
    }

    public function test_global_highlights_are_separate_from_base_color_and_dark_mode_inherits_light(): void
    {
        $before = Frontend::profileColors();
        config(['laravelusers.profileCardGradientHighlightColor' => '#91c246']);
        $after = Frontend::profileColors();
        $this->assertSame('#91c24648', $after['highlight']);
        $this->assertSame(array_diff_key($before, ['highlight' => true]), array_diff_key($after, ['highlight' => true]));
        $this->assertSame('#91c24648', Frontend::profileColors(dark: true)['highlight']);
        config(['laravelusers.profileCardDarkGradientHighlightColor' => '#6dafe2', 'laravelusers.profileCardDarkGradientStrength' => 75]);
        $this->assertSame('#6dafe26c', Frontend::profileColors(dark: true)['highlight']);
        $this->assertSame('#91c24648', Frontend::profileColors()['highlight']);
    }

    public function test_individual_highlights_persist_independently_and_return_to_inheritance(): void
    {
        $this->enableAppearance();
        $user = $this->user();
        $this->actingAs($user);
        $data = ['name' => $user->name, 'email' => $user->email];
        $this->put('/users/'.$user->id, $data + ['user_card_color' => '#264e36', 'user_card_gradient_highlight_color' => '#f6be43', 'user_card_dark_gradient_highlight_color' => '#72c5d9', 'user_card_dark_gradient_strength' => 100])->assertSessionHasNoErrors();
        $colors = AppearancePreferences::colors([$user])[$user->id];
        $this->assertSame('#264e36', $colors['base']);
        $this->assertSame('#f6be4348', $colors['highlight']);
        $this->assertSame('#72c5d990', $colors['dark']['highlight']);
        config(['laravelusers.profileCardGradientHighlightColor' => '#b14c8a']);
        $this->put('/users/'.$user->id, $data)->assertSessionHasNoErrors();
        $this->assertSame('#f6be43', AppearancePreferences::listing([$user])[$user->id]['highlight_color']);
        $this->postJson('/search-users', ['user_search_box' => $user->name, 'include_avatar' => 1])->assertOk()->assertJsonPath('appearance.'.$user->id.'.highlight', '#f6be4348');
        foreach (Frontend::FRAMEWORKS as $framework) {
            config(['laravelusers.frontend' => $framework]);
            $this->get('/users/'.$user->id.'/edit')->assertOk()->assertSee('name="user_card_gradient_highlight_color"', false)->assertSee('name="user_card_dark_gradient_highlight_color"', false);
            $this->get('/users/'.$user->id)->assertOk()->assertSee('--lu-profile-glow: #f6be4348', false)->assertSee('--lu-profile-dark-glow: #72c5d990', false);
        }
        $this->put('/users/'.$user->id, $data + ['user_card_gradient_highlight_color' => '', 'user_card_dark_gradient_highlight_color' => ''])->assertSessionHasNoErrors();
        $colors = AppearancePreferences::colors([$user])[$user->id];
        $this->assertSame('#b14c8a48', $colors['highlight']);
        $this->assertSame('#b14c8a90', $colors['dark']['highlight']);
        config(['laravelusers.profileCardDarkGradientHighlightColor' => '#139481']);
        $this->assertSame('#13948190', AppearancePreferences::colors([$user])[$user->id]['dark']['highlight']);
    }

    public function test_missing_additive_columns_ignore_highlight_inputs_and_keep_existing_preferences(): void
    {
        $this->enableAppearance(['2026_10_09_043153_add_gradient_highlight_colors_to_laravelusers_appearance_preferences_table.php']);
        $user = $this->user();
        AppearancePreferences::save($user, ['user_card_color' => '#264e36']);
        $this->assertFalse(AppearancePreferences::formData($user)['appearanceHighlightAvailable']);
        $this->actingAs($user)->get('/users/'.$user->id.'/edit')->assertOk()->assertDontSee('name="user_card_gradient_highlight_color"', false);
        $this->put('/users/'.$user->id, ['name' => 'Updated', 'email' => $user->email, 'user_card_gradient_highlight_color' => '#abcdef', 'user_card_dark_gradient_highlight_color' => 'invalid'])->assertSessionHasNoErrors();
        $this->assertSame('Updated', $user->fresh()->name);
        if (method_exists(Model::class, 'preventAccessingMissingAttributes')) {
            Model::preventAccessingMissingAttributes();
        }

        try {
            $this->assertSame(['color' => '#264e36', 'gradient' => null], AppearancePreferences::listing([$user])[$user->id]);
            $this->assertSame('#ffffff48', AppearancePreferences::colors([$user])[$user->id]['highlight']);
        } finally {
            if (method_exists(Model::class, 'preventAccessingMissingAttributes')) {
                Model::preventAccessingMissingAttributes(false);
            }
        }
    }

    public function test_additive_migration_preserves_existing_values_and_rolls_back_only_its_columns(): void
    {
        $this->enableAppearance(['2026_10_09_043153_add_gradient_highlight_colors_to_laravelusers_appearance_preferences_table.php']);
        $user = $this->user();
        AppearancePreferences::save($user, ['user_card_color' => '#264e36', 'user_card_gradient' => 'off', 'user_card_gradient_strength' => 70]);
        $columns = Schema::getColumnListing('users');
        $migration = require dirname(__DIR__, 2).'/src/database/appearance/2026_10_09_043153_add_gradient_highlight_colors_to_laravelusers_appearance_preferences_table.php';
        $migration->up();
        $this->assertTrue(AppearancePreferences::highlightAvailable($user));
        $this->assertSame(['color' => '#264e36', 'gradient' => false, 'strength' => 70], AppearancePreferences::listing([$user])[$user->id]);
        AppearancePreferences::save($user, ['user_card_gradient_highlight_color' => '#f6be43']);
        $migration->down();
        $this->assertFalse(AppearancePreferences::highlightAvailable($user));
        $this->assertSame($columns, Schema::getColumnListing('users'));
        $this->assertSame(['color' => '#264e36', 'gradient' => false, 'strength' => 70], AppearancePreferences::listing([$user])[$user->id]);
    }

    public function test_invalid_and_unauthorized_highlights_do_not_change_users(): void
    {
        $this->enableAppearance();
        $user = $this->user();
        $this->actingAs($user);
        foreach (['#123', 'red', '#fff; color:red', ['#123456']] as $color) {
            foreach (['user_card_gradient_highlight_color', 'user_card_dark_gradient_highlight_color'] as $field) {
                $this->put('/users/'.$user->id, ['name' => 'Changed', 'email' => $user->email, $field => $color])->assertSessionHasErrors($field);
            }
        }
        config(['laravelusers.settings.enabled' => true, 'laravelusers.access.edit_user_appearance.mode' => 'deny']);
        $this->put('/users/'.$user->id, ['name' => 'Changed', 'email' => $user->email, 'user_card_gradient_highlight_color' => '#123456'])->assertSessionHasErrors('user_card_gradient_highlight_color');
        $this->assertSame($user->name, $user->fresh()->name);
        $this->assertDatabaseCount('laravelusers_appearance_preferences', 0);
    }

    public function test_global_highlights_and_default_off_breadcrumbs_persist_with_existing_permissions(): void
    {
        $this->enableSettings();
        $user = $this->user();
        $this->actingAs($user);
        $this->assertFalse((bool) config('laravelusers.showBreadcrumbs'));
        foreach (Frontend::FRAMEWORKS as $framework) {
            config(['laravelusers.frontend' => $framework]);
            $this->get('/users/settings')->assertOk()->assertSee('name="show_breadcrumbs"', false)->assertSee('data-lu-icon="breadcrumbs"', false)->assertSee('name="profile_gradient_highlight_color"', false);
            $this->get('/users')->assertOk()->assertDontSee('<nav class="lu-breadcrumbs"', false);
        }
        $data = ['avatar_source' => 'initials', 'profile_color' => '#264e36', 'edit_color' => '#705000'];
        $values = ['profile_gradient_highlight_color' => '#f6be43', 'edit_gradient_highlight_color' => '#72c5d9', 'profile_dark_gradient_highlight_color' => '#b14c8a', 'edit_dark_gradient_highlight_color' => '#139481', 'show_breadcrumbs' => 1];
        $this->put('/users/settings', $data + $values)->assertSessionHasNoErrors();
        $this->get('/users')->assertOk()->assertSee('<nav class="lu-breadcrumbs"', false);
        $this->assertTrue(config('laravelusers.showBreadcrumbs'));
        $this->assertSame('#f6be4348', Frontend::profileColors()['highlight']);
        $this->assertSame('#b14c8a48', Frontend::profileColors(dark: true)['highlight']);
        $this->put('/users/settings', $data + ['profile_dark_gradient_highlight_color' => '', 'edit_dark_gradient_highlight_color' => '', 'show_breadcrumbs' => 0])->assertSessionHasNoErrors();
        $this->get('/users')->assertOk()->assertDontSee('<nav class="lu-breadcrumbs"', false);
        $this->assertNull(UserSetting::findOrFail('global')->value['profileCardDarkGradientHighlightColor']);
        $this->assertSame('#f6be4348', Frontend::profileColors(dark: true)['highlight']);
        foreach (['profile_gradient_highlight_color' => 'red', 'edit_gradient_highlight_color' => '#123', 'profile_dark_gradient_highlight_color' => '#123456;', 'show_breadcrumbs' => 'yes'] as $field => $value) {
            $this->put('/users/settings', $data + [$field => $value])->assertSessionHasErrors($field);
        }
        UserSetting::findOrFail('global')->update(['value' => ['access' => ['edit_appearance' => ['mode' => 'deny']]]]);
        $this->put('/users/settings', ['show_breadcrumbs' => 1, 'profile_gradient_highlight_color' => '#123456'])->assertSessionHasErrors(['show_breadcrumbs', 'profile_gradient_highlight_color']);
    }
}
