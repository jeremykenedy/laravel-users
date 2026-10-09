<?php

namespace jeremykenedy\laravelusers\Test\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use jeremykenedy\laravelusers\Models\UserSetting;
use jeremykenedy\laravelusers\Notifications\UserMessage;
use jeremykenedy\laravelusers\Support\AccountLinks;
use jeremykenedy\laravelusers\Test\Fixtures\SoftUser;
use jeremykenedy\laravelusers\Test\TestCase;

class GoodbyeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['laravelusers.emails.enabled' => true, 'laravelusers.welcome.enabled' => true]);
        Notification::fake();
        Schema::table('users', fn (Blueprint $table) => $table->softDeletes());
        config(['laravelusers.defaultUserModel' => SoftUser::class, 'laravelusers.softDeletedEnabled' => true, 'laravelusers.bulkActions' => true]);
        $this->actingAs(SoftUser::findOrFail($this->user()->id));
    }

    public function test_existing_deletions_send_nothing_and_disabled_goodbye_cannot_be_forged(): void
    {
        $recipient = $this->user();
        $this->delete('/users/'.$recipient->id, ['send_goodbye' => 1])->assertSessionHasErrors('send_goodbye');
        $this->assertNotNull($recipient->fresh());
        $this->delete('/users/'.$recipient->id)->assertRedirect('/users');
        Notification::assertNothingSent();
    }

    public function test_individual_and_bulk_deletions_send_personalized_editable_messages(): void
    {
        config(['laravelusers.emails.goodbye' => true]);
        $recipient = $this->user(['name' => 'Casey']);
        $data = ['send_goodbye' => 1, 'goodbye' => ['subject' => 'See you soon', 'message' => 'Your account is closed.', 'use_greeting' => 1, 'greeting' => 'Hello', 'include_name' => 1, 'use_signoff' => 1, 'signoff' => 'Regards', 'signoff_name' => 'The team']];
        $this->delete('/users/'.$recipient->id, $data)->assertRedirect('/users')->assertSessionHasNoErrors();
        Notification::assertSentOnDemand(UserMessage::class, function ($notification, $channels, $notifiable) use ($recipient) {
            $this->assertTrue(SoftUser::withTrashed()->findOrFail($recipient->id)->trashed());
            $mail = $notification->toMail($notifiable);
            $this->assertSame('See you soon', $mail->subject);
            $html = (string) $mail->render();
            $this->assertStringContainsString('Hello Casey,', $html);
            $this->assertStringContainsString('Your account is closed.', $html);
            $this->assertStringContainsString('The team', $html);
            $this->assertSame($recipient->email, $notifiable->routes['mail']);

            return $channels === ['mail'];
        });
        Notification::fake();
        $first = $this->user(['name' => 'Taylor', 'email' => 'taylor@example.com']);
        $second = $this->user(['name' => 'Jordan', 'email' => 'jordan@example.com']);
        $this->post('/users/bulk', $data + ['action' => 'delete', 'ids' => [$first->id, $second->id]])->assertRedirect('/users');
        $this->assertCount(2, Notification::sent(new AnonymousNotifiable(), UserMessage::class));
        $this->assertSame(['Taylor', 'Jordan'], Notification::sent(new AnonymousNotifiable(), UserMessage::class)->pluck('name')->all());
    }

    public function test_automatic_goodbye_applies_only_to_deletions_and_self_delete_protection_remains(): void
    {
        config(['laravelusers.emails.goodbye' => true, 'laravelusers.emails.goodbye_auto_send' => true]);
        $this->delete('/users/1')->assertSessionHas('error');
        Notification::assertNothingSent();
        $recipient = $this->user();
        $this->delete('/users/'.$recipient->id)->assertRedirect('/users');
        $this->assertCount(1, Notification::sent(new AnonymousNotifiable(), UserMessage::class));
        Notification::fake();
        $this->post('/users/'.$recipient->id.'/restore')->assertRedirect();
        $this->delete('/users/'.$recipient->id)->assertRedirect();
        Notification::fake();
        $this->delete('/users/'.$recipient->id.'/force')->assertRedirect();
        Notification::assertNothingSent();
    }

    public function test_goodbye_options_follow_permissions_and_validation(): void
    {
        config(['laravelusers.emails.goodbye' => true, 'laravelusers.settings.enabled' => true, 'laravelusers.access.email_goodbye.mode' => 'deny']);
        $recipient = $this->user();
        $this->delete('/users/'.$recipient->id, ['send_goodbye' => 1])->assertSessionHasErrors('send_goodbye');
        $this->get('/users')->assertOk()->assertDontSee('<div data-lu-goodbye-options', false);
        config(['laravelusers.access.email_goodbye.mode' => 'inherit']);
        $this->delete('/users/'.$recipient->id, ['send_goodbye' => 1, 'goodbye' => ['subject' => "Hello\r\nBcc: bad@example.com", 'message' => 'Body']])->assertSessionHasErrors('goodbye.subject');
        $this->post('/users/bulk', ['action' => 'delete', 'ids' => [1, $recipient->id], 'send_goodbye' => 1])->assertSessionHasErrors('ids');
        $this->assertFalse(SoftUser::withTrashed()->findOrFail($recipient->id)->trashed());
        Notification::assertNothingSent();
    }

    public function test_global_templates_persist_without_overwriting_other_settings(): void
    {
        config(['laravelusers.settings.enabled' => true]);
        (require dirname(__DIR__, 2).'/src/database/settings/2026_10_08_145311_create_laravelusers_settings_table.php')->up();
        Gate::define('manage-laravelusers-settings', fn () => true);
        UserSetting::create(['key' => 'global', 'value' => ['profileCardColor' => '#123456']]);
        $data = ['welcome_enabled' => 0, 'goodbye' => 1, 'goodbye_on_delete' => 0, 'goodbye_auto_send' => 1, 'templates' => ['goodbye' => ['subject' => 'Goodbye from our team', 'message' => 'Thank you for joining us.']]];
        $this->put('/users/settings/emails', $data)->assertRedirect()->assertSessionHasNoErrors();
        $this->delete('/users/'.$this->user()->id)->assertRedirect();
        Notification::assertSentOnDemand(UserMessage::class, fn ($notification) => $notification->contents['subject'] === 'Goodbye from our team' && $notification->contents['message'] === 'Thank you for joining us.');
        $this->assertSame('#123456', UserSetting::findOrFail('global')->value['profileCardColor']);
        config(['laravelusers.access.edit_email_templates.mode' => 'deny']);
        $this->putJson('/users/settings/emails', $data)->assertForbidden();
    }

    public function test_goodbye_links_use_cleanup_expiration_and_are_consumed_once(): void
    {
        (require dirname(__DIR__, 2).'/src/database/account-links/2026_10_08_000000_create_laravelusers_account_links_table.php')->up();
        config(['laravelusers.emails.goodbye' => true, 'laravelusers.emails.goodbye_auto_send' => true, 'laravelusers.account_links.enabled' => true, 'laravelusers.cleanup.enabled' => true, 'laravelusers.emails.goodbye_restore' => true, 'laravelusers.emails.goodbye_force_delete' => true, 'laravelusers.emails.goodbye_retention' => true, 'laravelusers.emails.goodbye_expiry_mode' => 'cleanup']);
        $recipient = $this->user();
        $this->delete('/users/'.$recipient->id)->assertRedirect('/users');
        Notification::assertSentOnDemand(UserMessage::class, function ($notification, $channels, $notifiable) {
            $this->assertCount(2, $notification->accountLinks);
            $token = basename(parse_url($notification->accountLinks['restore'], PHP_URL_PATH));
            $links = $this->app->make(AccountLinks::class);
            $link = $links->inspect($token);
            $this->assertNotNull($link);
            $this->assertSame(now()->addDays(180)->timestamp, $link->expires_at);
            $html = (string) $notification->toMail($notifiable)->render();
            $this->assertStringContainsString('Restore my account', $html);
            $this->assertStringContainsString('Permanently delete my account', $html);
            $this->assertStringContainsString('eligible for permanent deletion', $html);
            $this->assertSame('restore', $links->consume($token));
            $this->assertNull($links->consume($token));
            $this->assertNull($links->inspect(basename(parse_url($notification->accountLinks['force_delete'], PHP_URL_PATH))));

            return true;
        });
    }

    public function test_goodbye_links_respect_custom_and_nonexpiring_settings(): void
    {
        $this->travelTo(Carbon::parse('2026-10-08 12:00:00 UTC'));
        (require dirname(__DIR__, 2).'/src/database/account-links/2026_10_08_000000_create_laravelusers_account_links_table.php')->up();
        config(['laravelusers.emails.goodbye' => true, 'laravelusers.emails.goodbye_auto_send' => true, 'laravelusers.account_links.enabled' => true, 'laravelusers.emails.goodbye_restore' => true, 'laravelusers.emails.goodbye_duration' => 4, 'laravelusers.emails.goodbye_unit' => 'hours']);
        foreach (['custom' => now()->addHours(4)->timestamp, 'never' => null] as $mode => $expiry) {
            Notification::fake();
            config(['laravelusers.emails.goodbye_expiry_mode' => $mode]);
            $recipient = $this->user();
            $this->delete('/users/'.$recipient->id)->assertRedirect('/users');
            Notification::assertSentOnDemand(UserMessage::class, function ($notification) use ($expiry): bool {
                $token = basename(parse_url($notification->accountLinks['restore'], PHP_URL_PATH));
                $link = $this->app->make(AccountLinks::class)->inspect($token);
                $this->assertNotNull($link);
                $this->assertSame($expiry, $link->expires_at);
                $this->assertSame($expiry, $notification->contents['linkExpiry'] === null ? null : Carbon::parse($notification->contents['linkExpiry'])->timestamp);

                return true;
            });
        }
    }
}
