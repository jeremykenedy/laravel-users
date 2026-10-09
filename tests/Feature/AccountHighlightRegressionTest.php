<?php

namespace jeremykenedy\laravelusers\Test\Feature;

use Illuminate\Routing\Middleware\ThrottleRequests;
use jeremykenedy\laravelusers\Support\AppearancePreferences;
use jeremykenedy\laravelusers\Test\TestCase;

class AccountHighlightRegressionTest extends TestCase
{
    public function test_bootstrap4_account_highlights_save_and_reset_to_inheritance(): void
    {
        $this->saveHighlights('bootstrap4');
    }

    public function test_bootstrap5_account_highlights_save_and_reset_to_inheritance(): void
    {
        $this->saveHighlights('bootstrap5');
    }

    public function test_disabled_account_appearance_cannot_change_or_clear_highlights(): void
    {
        $this->install();
        $user = $this->user();
        $this->actingAs($user);
        AppearancePreferences::save($user, ['user_card_gradient_highlight_color' => '#f6be43', 'user_card_dark_gradient_highlight_color' => '#72c5d9']);
        config(['laravelusers.account.appearance' => false, 'laravelusers.account.avatar' => true, 'laravelusers.appearance.per_user' => true]);

        foreach (['bootstrap4', 'bootstrap5'] as $framework) {
            config(['laravelusers.frontend' => $framework]);
            foreach (['#123456', '', null] as $value) {
                $response = $this->putJson('/users/account', ['section' => 'appearance', 'user_card_gradient_highlight_color' => $value, 'user_card_dark_gradient_highlight_color' => $value]);
                $this->assertSame(422, $response->getStatusCode(), $framework.' '.json_encode($value));
                $response->assertJsonValidationErrors(['user_card_gradient_highlight_color', 'user_card_dark_gradient_highlight_color']);
                $saved = AppearancePreferences::listing([$user])[$user->id];
                $this->assertSame('#f6be43', $saved['highlight_color']);
                $this->assertSame('#72c5d9', $saved['dark_highlight_color']);
            }
        }
    }

    public function test_invalid_highlights_do_not_change_existing_appearance(): void
    {
        $this->install();
        $this->withoutMiddleware(ThrottleRequests::class);
        $user = $this->user();
        $this->actingAs($user);
        AppearancePreferences::save($user, ['user_card_color' => '#204070']);
        $before = AppearancePreferences::listing([$user])[$user->id];

        foreach (['bootstrap4', 'bootstrap5'] as $framework) {
            config(['laravelusers.frontend' => $framework]);
            foreach (['red', '#fff', '#123456; color:red', ['#123456']] as $value) {
                $this->putJson('/users/account', ['section' => 'appearance', 'user_card_color' => '#123456', 'user_card_gradient_highlight_color' => $value, 'user_card_dark_gradient_highlight_color' => $value])
                    ->assertUnprocessable()->assertJsonValidationErrors(['user_card_gradient_highlight_color', 'user_card_dark_gradient_highlight_color']);
                $this->assertSame($before, AppearancePreferences::listing([$user])[$user->id]);
            }
        }
    }

    public function test_hosts_without_highlight_columns_preserve_appearance_and_ignore_unavailable_fields(): void
    {
        $this->install(['2026_10_09_043153_add_gradient_highlight_colors_to_laravelusers_appearance_preferences_table.php']);
        $user = $this->user();
        $this->actingAs($user);
        AppearancePreferences::save($user, ['user_card_color' => '#204070']);

        foreach (['bootstrap4', 'bootstrap5'] as $framework) {
            config(['laravelusers.frontend' => $framework]);
            $this->get('/users/account')->assertOk()
                ->assertDontSee('name="user_card_gradient_highlight_color"', false)
                ->assertDontSee('name="user_card_dark_gradient_highlight_color"', false);
            $this->put('/users/account', ['section' => 'appearance', 'user_card_gradient_highlight_color' => '#f6be43', 'user_card_dark_gradient_highlight_color' => 'invalid'])
                ->assertRedirect()->assertSessionHasNoErrors();
            $saved = AppearancePreferences::listing([$user])[$user->id];
            $this->assertSame('#204070', $saved['color']);
            $this->assertArrayNotHasKey('highlight_color', $saved);
            $this->assertArrayNotHasKey('dark_highlight_color', $saved);
        }
    }

    private function install(array $excludedMigrations = []): void
    {
        foreach (['accounts', 'appearance'] as $feature) {
            foreach (glob(dirname(__DIR__, 2).'/src/database/'.$feature.'/*.php') as $path) {
                if (!in_array(basename($path), $excludedMigrations, true)) {
                    (require $path)->up();
                }
            }
        }
        config(['laravelusers.account.enabled' => true, 'laravelusers.account.settings_enabled' => true]);
    }

    private function saveHighlights(string $framework): void
    {
        $this->install();
        config(['laravelusers.frontend' => $framework, 'laravelusers.profileCardGradientHighlightColor' => '#112233', 'laravelusers.profileCardDarkGradientHighlightColor' => null]);
        $user = $this->user();
        $this->actingAs($user)->get('/users/account')->assertOk()
            ->assertSee('name="user_card_gradient_highlight_color"', false)
            ->assertSee('name="user_card_dark_gradient_highlight_color"', false);

        $this->put('/users/account', ['section' => 'appearance', 'user_card_color' => '#204070', 'user_card_gradient_highlight_color' => '#f6be43', 'user_card_dark_gradient_highlight_color' => '#72c5d9'])
            ->assertRedirect()->assertSessionHasNoErrors();

        $saved = AppearancePreferences::listing([$user])[$user->id] ?? [];
        $this->assertSame('#f6be43', $saved['highlight_color'] ?? null);
        $this->assertSame('#72c5d9', $saved['dark_highlight_color'] ?? null);

        $this->put('/users/account', ['section' => 'appearance', 'user_card_gradient_highlight_color' => '', 'user_card_dark_gradient_highlight_color' => ''])
            ->assertRedirect()->assertSessionHasNoErrors();
        $saved = AppearancePreferences::listing([$user])[$user->id];
        $this->assertSame('#204070', $saved['color']);
        $this->assertArrayNotHasKey('highlight_color', $saved);
        $this->assertArrayNotHasKey('dark_highlight_color', $saved);
        $colors = AppearancePreferences::colors([$user])[$user->id];
        $this->assertSame('#11223348', $colors['highlight']);
        $this->assertSame('#11223348', $colors['dark']['highlight']);
    }
}
