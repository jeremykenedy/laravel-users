<?php

namespace jeremykenedy\laravelusers\Test\Feature;

use DOMDocument;
use DOMXPath;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
use Jeremykenedy\LaravelToast\Facades\Toast;
use Jeremykenedy\LaravelToast\Providers\ToastServiceProvider;
use jeremykenedy\laravelusers\Models\UserSetting;
use jeremykenedy\laravelusers\Support\ToastSettings;
use jeremykenedy\laravelusers\Support\UserNotifications;
use jeremykenedy\laravelusers\Support\UserSettings;
use jeremykenedy\laravelusers\Test\TestCase;

class ToastSettingsTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->directory = sys_get_temp_dir().'/laravelusers-toast-settings-'.bin2hex(random_bytes(8));
        $this->app->setBasePath($this->directory);
        File::ensureDirectoryExists(config_path());
        File::put(base_path('composer.json'), json_encode(['autoload' => ['psr-4' => ['App\\' => 'app/']]]));
        (require dirname(__DIR__, 2).'/src/database/settings/2026_10_08_145311_create_laravelusers_settings_table.php')->up();
        config(['laravelusers.settings.enabled' => true]);
        Gate::define('manage-laravelusers-settings', fn () => true);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->directory);
        parent::tearDown();
    }

    public function test_unavailable_toast_cannot_be_configured_and_alert_defaults_remain_available(): void
    {
        $this->assertFalse(UserNotifications::toastInstalled());
        $this->actingAs($this->user())->get('/users/settings')->assertOk()
            ->assertDontSee('name="toast[position]"', false)
            ->assertDontSee('name="notifications_driver"', false);

        $this->putJson('/users/settings', $this->payload(['notifications_driver' => 'toast', 'toast' => ['duration' => 1000]]))
            ->assertUnprocessable()->assertJsonValidationErrors(['notifications_driver', 'toast']);

        $this->assertNull(UserSetting::find('global'));
        $this->put('/users/settings', $this->payload())->assertSessionHasNoErrors();
        $this->assertArrayNotHasKey('toast', UserSetting::findOrFail('global')->value);
        $this->withSession(['success' => 'Ordinary alert'])->get('/users')->assertOk()->assertSee('Ordinary alert')->assertSee('class="lu-flash', false);
    }

    public function test_settings_gate_and_notification_access_protect_all_toast_options(): void
    {
        $this->installToast();
        $this->actingAs($this->user());
        Gate::define('manage-laravelusers-settings', fn () => false);
        $this->putJson('/users/settings', $this->payload(['toast' => ['duration' => 1000]]))->assertForbidden();
        $this->assertNull(UserSetting::find('global'));
        Gate::define('manage-laravelusers-settings', fn () => true);
        (new UserSettings())->save(['access' => ['edit_notifications' => ['mode' => 'deny']], 'toast' => ['duration' => 7000], 'notifications.driver' => 'both']);
        $before = UserSetting::findOrFail('global')->value;

        $this->putJson('/users/settings', $this->payload(['notifications_driver' => 'alert', 'toast' => ['duration' => 1000]]))
            ->assertUnprocessable()->assertJsonValidationErrors(['notifications_driver', 'toast']);

        $this->assertSame($before, UserSetting::findOrFail('global')->value);
        $page = $this->get('/users/settings')->assertOk();
        $this->assertSame(1, $this->document($page->getContent())->query('//fieldset[contains(@class,"lu-settings-notifications") and @disabled]')->length);
        $this->put('/users/settings', $this->payload(['profile_color' => '#112233']))->assertSessionHasNoErrors();
        $after = UserSetting::findOrFail('global')->value;
        $this->assertSame(7000, $after['toast']['duration']);
        $this->assertSame('both', $after['notifications.driver']);
    }

    public function test_unknown_and_nested_toast_options_are_rejected_before_persistence(): void
    {
        $this->installToast();
        $this->withoutMiddleware(ThrottleRequests::class);
        $this->actingAs($this->user());
        foreach ([
            [['custom_icon' => '<svg onload="alert(1)"></svg>'], 'toast'],
            [['broadcast' => ['enabled' => true, 'channel' => 'private-users']], 'toast'],
            [['session_key' => 'auth'], 'toast'],
            [['css_framework' => 'other'], 'toast'],
            [['frontend' => 'other'], 'toast'],
            [['position' => ['top-right']], 'toast.position'],
            [['duration' => ['value' => 1]], 'toast.duration'],
            [['auto_dismiss' => ['value' => true]], 'toast.auto_dismiss'],
            [['duration' => -1], 'toast.duration'],
            [['duration' => 3600001], 'toast.duration'],
            [['duration' => 1.5], 'toast.duration'],
            [['opacity' => 1.1], 'toast.opacity'],
            [['enter_animation' => 'fade; background:url(javascript:alert(1))'], 'toast.enter_animation'],
            [['position' => '" onmouseover="alert(1)'], 'toast.position'],
        ] as [$options, $error]) {
            $this->putJson('/users/settings', $this->payload(['toast' => $options]))->assertUnprocessable()->assertJsonValidationErrors($error);
            $this->assertNull(UserSetting::find('global'));
        }
    }

    public function test_saved_options_preserve_host_config_files_and_unspecified_defaults(): void
    {
        $contents = "<?php return ['position' => 'bottom-center', 'duration' => 8200, 'show_close' => false, 'session_key' => 'host_toasts', 'broadcast' => ['enabled' => false, 'channel' => 'host.{userId}'], 'host_option' => 'preserved'];\n";
        File::put(config_path('toast.php'), $contents);
        config(['toast' => require config_path('toast.php')]);
        $this->installToast();
        $this->assertSame('bottom-center', ToastSettings::values()['position']);
        $this->assertSame(8200, ToastSettings::values()['duration']);
        $this->assertFalse(ToastSettings::values()['show_close']);

        $this->actingAs($this->user())->put('/users/settings', $this->payload(['notifications_driver' => 'both', 'toast' => ['duration' => '1750']]))->assertSessionHasNoErrors();
        (new UserSettings())->load();

        $stored = UserSetting::findOrFail('global')->value['toast'];
        $this->assertSame(1750, $stored['duration']);
        $this->assertSame('bottom-center', $stored['position']);
        $this->assertFalse($stored['show_close']);
        $this->assertSame('host_toasts', config('toast.session_key'));
        $this->assertSame('host.{userId}', config('toast.broadcast.channel'));
        $this->assertSame('preserved', config('toast.host_option'));
        $this->assertArrayNotHasKey('session_key', $stored);
        $this->assertArrayNotHasKey('broadcast', $stored);
        $this->assertArrayNotHasKey('host_option', $stored);
        $this->assertSame($contents, File::get(config_path('toast.php')));
    }

    public function test_global_overrides_change_real_payloads_and_both_notification_views(): void
    {
        $this->installToast();
        $options = ['position' => 'bottom-left', 'duration' => '1750', 'auto_dismiss' => '0', 'pause_on_hover' => '0', 'show_icons' => '0', 'show_border' => '0', 'show_close' => '0', 'show_progress' => '0', 'convert_flash' => '1', 'opacity' => '0.45'];
        $this->actingAs($this->user())->put('/users/settings', $this->payload(['notifications_driver' => 'both', 'toast' => $options]))->assertSessionHasNoErrors();
        config(['toast.position' => 'top-right', 'toast.duration' => 5000, 'toast.auto_dismiss' => true, 'laravelusers.notifications.driver' => 'alert']);
        $this->actingAs($this->user())->withSession(['success' => 'Global toast settings'])->get('/users')->assertOk();
        $session = $this->app->make('session.store');
        $session->put('success', 'Global toast settings');
        $payload = UserNotifications::toasts($session);
        $this->assertCount(1, $payload);
        $this->assertSame('bottom-left', $payload[0]['position']);
        $this->assertSame(1750, $payload[0]['duration']);
        $this->assertFalse($payload[0]['auto_dismiss']);
        $this->assertFalse($payload[0]['pause_on_hover']);
        $this->assertFalse($payload[0]['show_icon']);
        $this->assertFalse($payload[0]['show_close']);
        $this->assertSame(0.45, $payload[0]['opacity']);

        $page = $this->withSession(['success' => 'Global toast settings'])->get('/users')->assertOk()->assertSee('class="lu-flash', false);
        $document = $this->document($page->getContent());
        $this->assertSame(1, $document->query('//*[@data-lu-toast-position="bottom-left"]')->length);
        $this->assertSame(1, $document->query('//*[@data-lu-toast and @data-duration="1750" and @data-auto-dismiss="false" and @data-pause-on-hover="false"]')->length);
        $this->assertSame(0, $document->query('//*[@data-lu-toast]//*[@data-lu-dismiss-toast or @data-lu-toast-progress]')->length);
        $this->assertSame(0, $document->query('//*[@data-lu-toast]//*[contains(@class,"lu-toast-icon")]')->length);
        $settings = $this->get('/users/settings')->assertOk();
        $this->assertSame(1, $this->document($settings->getContent())->query('//select[@name="toast[position]"]/option[@value="bottom-left" and @selected]')->length);
        $this->assertSame(1, $this->document($settings->getContent())->query('//input[@name="toast[duration]" and @value="1750"]')->length);
    }

    public function test_stack_and_maximum_count_apply_to_the_real_toast_payload(): void
    {
        $this->installToast();
        $this->actingAs($this->user())->put('/users/settings', $this->payload(['notifications_driver' => 'toast', 'toast' => ['stack' => '1', 'max_visible' => '2']]))->assertSessionHasNoErrors();
        (new UserSettings())->load();
        $session = $this->app->make('session.store');
        $session->replace(['message' => 'First', 'success' => 'Second', 'error' => 'Third']);

        $this->assertSame(['Second', 'Third'], array_column(UserNotifications::toasts($session), 'message'));

        $this->put('/users/settings', $this->payload(['toast' => ['stack' => '0']]))->assertSessionHasNoErrors();
        (new UserSettings())->load();
        $session->replace(['message' => 'First', 'success' => 'Second', 'error' => 'Third']);
        $this->assertSame(['Third'], array_column(UserNotifications::toasts($session), 'message'));
    }

    public function test_disabling_flash_conversion_preserves_explicit_toasts_and_ordinary_alerts(): void
    {
        $this->installToast();
        $this->actingAs($this->user())->put('/users/settings', $this->payload(['notifications_driver' => 'both', 'toast' => ['convert_flash' => '0']]))->assertSessionHasNoErrors();
        (new UserSettings())->load();
        $session = $this->app->make('session.store');
        $session->replace(['success' => 'Ordinary alert']);
        Toast::success('Explicit toast');

        $this->assertSame(['Explicit toast'], array_column(UserNotifications::toasts($session), 'message'));
        $this->assertSame('Ordinary alert', $session->get('success'));
        $this->get('/users')->assertOk()->assertSee('Explicit toast')->assertSee('Ordinary alert')->assertSee('class="lu-flash', false);
    }

    public function test_toast_payload_excludes_raw_icons_and_unrelated_internal_fields(): void
    {
        $this->installToast();
        config(['laravelusers.notifications.driver' => 'toast']);
        $session = $this->app->make('session.store');
        $toast = Toast::build('success', 'Saved', 'Title', null, ['custom_icon' => '<svg onload="alert(1)"></svg>']);
        $session->put(config('toast.session_key'), [$toast + ['private_context' => 'private', 'redirect' => 'javascript:alert(1)']]);

        $payload = UserNotifications::toasts($session);

        $this->assertCount(1, $payload);
        $this->assertSame('Saved', $payload[0]['message']);
        foreach (['custom_icon', 'css_framework', 'stack', 'max_visible', 'timestamp', 'private_context', 'redirect'] as $key) {
            $this->assertArrayNotHasKey($key, $payload[0]);
        }
    }

    public function test_all_css_adapters_escape_toast_titles_messages_and_flash_messages(): void
    {
        $this->installToast();
        $this->actingAs($this->user());
        config(['laravelusers.notifications.driver' => 'both']);
        $message = '</span><script>alert("toast-message")</script><img src=x onerror=alert(1)>';
        $title = '<svg onload="alert(2)">Title</svg>';
        foreach (['bootstrap4', 'bootstrap5', 'tailwind', 'materialize', 'material3', 'bulma', 'foundation'] as $framework) {
            config(['laravelusers.frontend' => $framework]);
            $this->app->make('session.store')->replace([]);
            Toast::success($message, $title, null, ['custom_icon' => '<svg onload="alert(3)"></svg>']);

            $page = $this->withSession(['error' => $message])->get('/users')->assertOk()
                ->assertSee(e($message), false)->assertSee(e($title), false)
                ->assertDontSee($message, false)->assertDontSee($title, false)
                ->assertDontSee('<svg onload="alert(3)">', false);

            $document = $this->document($page->getContent());
            $this->assertSame(2, $document->query('//*[@data-lu-toast]')->length);
            $this->assertSame(0, $document->query('//*[@data-lu-toast]//script | //*[@data-lu-toast]//*[@onerror or @onload or @x-data]')->length);
        }
    }

    private function installToast(): void
    {
        if (!class_exists(ToastServiceProvider::class)) {
            $this->markTestSkipped('Run the optional presentation integration job to test Laravel Toast.');
        }
        $this->app->register(ToastServiceProvider::class);
        $this->assertTrue(UserNotifications::toastInstalled());
    }

    private function payload(array $changes = []): array
    {
        return array_replace(['avatar_source' => 'initials', 'profile_color' => '#2458b7', 'edit_color' => '#705000'], $changes);
    }

    private function document(string $html): DOMXPath
    {
        $previous = libxml_use_internal_errors(true);
        $document = new DOMDocument();
        $document->loadHTML($html);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        return new DOMXPath($document);
    }
}
