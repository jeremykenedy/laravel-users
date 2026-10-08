<?php

namespace jeremykenedy\laravelusers\Test\Feature;

use Illuminate\Support\Facades\Gate;
use Jeremykenedy\LaravelToast\Providers\ToastServiceProvider;
use jeremykenedy\laravelusers\Models\UserSetting;
use jeremykenedy\laravelusers\Support\UserNotifications;
use jeremykenedy\laravelusers\Test\TestCase;

class ToastIntegrationsTest extends TestCase
{
    public function test_installed_toast_renders_legacy_messages_and_success_notices_in_each_framework(): void
    {
        if (!class_exists(ToastServiceProvider::class)) {
            $this->markTestSkipped('Run the optional presentation integration job to test Laravel Toast.');
        }
        $this->actingAs($this->user());
        foreach (['bootstrap4', 'bootstrap5', 'tailwind'] as $framework) {
            config(['laravelusers.frontend' => $framework, 'laravelusers.notifications.driver' => 'toast', 'toast.css_framework' => $framework]);
            $this->app->register(ToastServiceProvider::class, true);
            $this->assertTrue(UserNotifications::toastInstalled());
            $this->withSession(['message' => 'Legacy notice', 'success' => 'Saved notice'])->get('/users')->assertOk()->assertSee('Legacy notice')->assertSee('Saved notice')->assertDontSee('class="lu-flash', false);
        }
    }

    public function test_toast_settings_only_appear_when_the_package_is_installed_and_respect_access(): void
    {
        if (!class_exists(ToastServiceProvider::class)) {
            $this->markTestSkipped('Run the optional presentation integration job to test Laravel Toast.');
        }
        $this->app->register(ToastServiceProvider::class);
        (require dirname(__DIR__, 2).'/src/database/settings/2026_10_08_145311_create_laravelusers_settings_table.php')->up();
        config(['laravelusers.settings.enabled' => true]);
        Gate::define('manage-laravelusers-settings', fn () => true);
        $this->actingAs($this->user())->get('/users/settings')->assertOk()->assertSee('name="notifications_driver"', false)->assertSee('Laravel Toast');
        $settings = ['avatar_source' => 'initials', 'profile_color' => '#2458b7', 'edit_color' => '#705000', 'notifications_driver' => 'toast'];
        $this->put('/users/settings', $settings)->assertSessionHasNoErrors();
        $this->withSession(['success' => 'Toast notice'])->get('/users')->assertOk()->assertSee('Toast notice')->assertDontSee('class="lu-flash', false);
        $record = UserSetting::findOrFail('global');
        $value = $record->value;
        $value['access']['edit_notifications'] = ['mode' => 'deny'];
        $record->update(['value' => $value]);
        $this->put('/users/settings', $settings)->assertSessionHasErrors('notifications_driver');
    }
}
