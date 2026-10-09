<?php

namespace jeremykenedy\laravelusers\Test\Feature;

use Illuminate\Auth\Passwords\CacheTokenRepository;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Notifications\Dispatcher;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Mail\Markdown;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use jeremykenedy\laravelusers\Actions\EmailUsers;
use jeremykenedy\laravelusers\Notifications\ResetUserPassword;
use jeremykenedy\laravelusers\Notifications\UserMessage;
use jeremykenedy\laravelusers\Notifications\WelcomeUser;
use jeremykenedy\laravelusers\Test\Fixtures\SoftUser;
use jeremykenedy\laravelusers\Test\Fixtures\User;
use jeremykenedy\laravelusers\Test\TestCase;
use RuntimeException;

class EmailUsersTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['laravelusers.emails.enabled' => true, 'laravelusers.welcome.enabled' => true]);
    }

    private function message(array $extra = []): array
    {
        return array_merge(['action' => 'message', 'ids' => [1], 'subject' => 'Account update', 'message' => "Your account is ready.\nPlease check your details.", 'use_greeting' => 1, 'greeting' => 'Hi', 'include_name' => 1, 'use_signoff' => 1, 'signoff' => 'Thanks', 'signoff_name' => 'Admin'], $extra);
    }

    private function resetRoutes(): void
    {
        Route::get('/reset-password/{token}', fn () => 'Reset')->name('password.reset');
        $this->app['router']->getRoutes()->refreshNameLookups();
        Schema::create(config('auth.passwords.users.table'), function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
    }

    public function test_email_actions_always_require_login_and_respect_the_configured_gate(): void
    {
        Notification::fake();
        config(['laravelusers.authEnabled' => false]);
        $this->post('/users/email', $this->message())->assertRedirect('/login');
        $this->actingAs($this->user());
        config(['laravelusers.emails.enabled' => false]);
        $this->post('/users/email', $this->message())->assertForbidden();
        Gate::define('manage-user-mail', fn () => false);
        config(['laravelusers.emails.enabled' => true, 'laravelusers.emails.gate' => 'manage-user-mail']);
        $this->post('/users/email', $this->message())->assertForbidden();
        $this->get('/users/1')->assertOk()->assertDontSee('data-lu-email-action="', false);
        Notification::assertNothingSent();
    }

    public function test_every_selected_user_is_authorized_before_any_email_is_queued(): void
    {
        Notification::fake();
        $owner = $this->user();
        $other = $this->user();
        Gate::policy(User::class, EmailUserPolicy::class);
        config(['laravelusers.bulkActions' => true]);
        $this->actingAs($owner)->post('/users/email', $this->message(['ids' => [$owner->id, $other->id]]))->assertForbidden();
        Notification::assertNothingSent();
    }

    public function test_bulk_messages_are_personalized_and_delivered_to_separate_recipients(): void
    {
        Notification::fake();
        $owner = $this->user(['name' => 'Morgan']);
        $other = $this->user(['name' => 'Sam']);
        config(['laravelusers.bulkActions' => true]);
        $this->actingAs($owner)->from('/users')->post('/users/email', $this->message(['ids' => [$owner->id, $other->id], 'email' => 'wrong@example.com']))->assertRedirect('/users')->assertSessionHasNoErrors();
        foreach ([$owner, $other] as $user) {
            Notification::assertSentOnDemand(UserMessage::class, function ($notification, $channels, $recipient) use ($user) {
                if ($recipient->routes['mail'] !== $user->email) {
                    return false;
                }
                $mail = $notification->toMail($recipient);
                $this->assertSame('Hi '.$user->name.',', $mail->viewData['greeting']);
                $this->assertSame("Thanks\nAdmin", $mail->viewData['signoff']);
                $this->assertSame(['mail'], $channels);
                $this->assertTrue(Hash::check('password', $user->fresh()->password));

                return true;
            });
        }
        Notification::assertSentTimes(UserMessage::class, 2);
    }

    public function test_custom_email_escapes_html_and_preserves_plain_text_and_optional_wrapping(): void
    {
        $name = '<img src=x onerror=alert(1)>';
        $contents = $this->message(['message' => "<script>alert(1)</script>\nSecond line", 'greeting' => '<b>Hi</b>', 'signoff_name' => '<a href=x>Admin</a>']);
        $mail = (new UserMessage($name, $contents))->toMail(null);
        $html = (string) $this->app->make(Markdown::class)->render($mail->markdown, $mail->data());
        $text = (string) $this->app->make(Markdown::class)->renderText($mail->markdown, $mail->data());
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringNotContainsString('<img src=x', $html);
        $this->assertStringContainsString('&lt;img src=x onerror=alert(1)&gt;', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertStringContainsString($contents['message'], $text);
        $mail = (new UserMessage($name, $this->message(['use_greeting' => 0, 'use_signoff' => 0])))->toMail(null);
        $this->assertSame('', $mail->viewData['greeting']);
        $this->assertSame('', $mail->viewData['signoff']);
        $mail = (new UserMessage('Sam', $this->message(['include_name' => 0])))->toMail(null);
        $this->assertSame('Hi', $mail->viewData['greeting']);
    }

    public function test_invalid_disabled_and_oversized_requests_send_nothing(): void
    {
        $this->withoutMiddleware(ThrottleRequests::class);
        Notification::fake();
        $this->actingAs($this->user());
        $this->post('/users/email', $this->message(['ids' => [1, 999]]))->assertSessionHasErrors('ids');
        config(['laravelusers.bulkActions' => true]);
        foreach ([['ids' => [1, 999]], ['ids' => [1, 1]], ['subject' => "Test\r\nBcc: other@example.com"], ['message' => ''], ['action' => 'delete']] as $extra) {
            $this->post('/users/email', $this->message($extra))->assertSessionHasErrors();
        }
        config(['laravelusers.bulkLimit' => 1]);
        $this->post('/users/email', $this->message(['ids' => [1, 2]]))->assertSessionHasErrors('ids');
        config(['laravelusers.emails.max_length' => 5]);
        $this->post('/users/email', $this->message())->assertSessionHasErrors('message');
        foreach (['message', 'reset', 'welcome'] as $action) {
            config(['laravelusers.emails.'.$action => false]);
            $this->post('/users/email', ['action' => $action, 'ids' => [1]])->assertSessionHasErrors('action');
        }
        Notification::assertNothingSent();
    }

    public function test_deleted_users_are_rejected_before_any_email_is_sent(): void
    {
        Notification::fake();
        Schema::table('users', fn (Blueprint $table) => $table->softDeletes());
        config(['laravelusers.defaultUserModel' => SoftUser::class, 'laravelusers.bulkActions' => true]);
        $owner = $this->user();
        $other = $this->user();
        SoftUser::findOrFail($other->id)->delete();
        $this->actingAs($owner)->post('/users/email', $this->message(['ids' => [$owner->id, $other->id]]))->assertSessionHasErrors('ids');
        Notification::assertNothingSent();
    }

    public function test_email_notifications_are_queued_and_welcome_does_not_change_passwords(): void
    {
        Queue::fake();
        $user = $this->user();
        $this->actingAs($user)->post('/users/email', $this->message())->assertSessionHasNoErrors();
        $this->post('/users/email', ['action' => 'welcome', 'ids' => [$user->id]])->assertSessionHasNoErrors();
        Queue::assertPushed(SendQueuedNotifications::class, fn ($job) => $job->notification instanceof UserMessage && $job->afterCommit);
        Queue::assertPushed(SendQueuedNotifications::class, fn ($job) => $job->notification instanceof WelcomeUser && $job->notification->resetUrl === null);
        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_reset_link_uses_the_host_broker_and_is_single_use(): void
    {
        Notification::fake();
        $this->resetRoutes();
        $user = $this->user();
        $this->actingAs($user)->post('/users/email', ['action' => 'reset', 'ids' => [$user->id]])->assertSessionHasNoErrors();
        Notification::assertSentOnDemand(ResetUserPassword::class, function ($notification, $channels, $recipient) use ($user) {
            $token = basename(parse_url($notification->url, PHP_URL_PATH));
            parse_str(parse_url($notification->url, PHP_URL_QUERY), $query);
            $this->assertSame($user->email, $query['email']);
            $this->assertSame($user->email, $recipient->routes['mail']);
            $this->assertTrue(Hash::check('password', $user->fresh()->password));
            $this->assertTrue(Password::broker()->tokenExists($user, $token));
            $result = Password::broker()->reset(['email' => $user->email, 'token' => $token, 'password' => 'replacement123', 'password_confirmation' => 'replacement123'], function ($user, $password) {
                $user->password = Hash::make($password);
                $user->save();
            });
            $this->assertSame(Password::PASSWORD_RESET, $result);
            $this->assertTrue(Hash::check('replacement123', $user->fresh()->password));
            $this->assertFalse(Password::broker()->tokenExists($user, $token));

            return true;
        });
    }

    public function test_reset_expiry_and_throttle_follow_the_selected_broker(): void
    {
        Notification::fake();
        $this->resetRoutes();
        config(['auth.passwords.staff' => array_merge(config('auth.passwords.users'), ['expire' => 5, 'throttle' => 60]), 'laravelusers.emails.password_broker' => 'staff']);
        $user = $this->user();
        $data = ['action' => 'reset', 'ids' => [$user->id]];
        $this->actingAs($user)->post('/users/email', $data)->assertSessionHasNoErrors();
        $this->post('/users/email', $data)->assertSessionHasErrors('email');
        Notification::assertSentTimes(ResetUserPassword::class, 1);
        Notification::assertSentOnDemand(ResetUserPassword::class, function ($notification) use ($user) {
            $this->assertSame(5, $notification->minutes);
            $token = basename(parse_url($notification->url, PHP_URL_PATH));
            $this->travel(6)->minutes();
            $this->assertFalse(Password::broker('staff')->tokenExists($user, $token));
            $this->travelBack();

            return true;
        });
    }

    public function test_reset_without_a_host_route_or_matching_provider_sends_nothing(): void
    {
        Notification::fake();
        $this->actingAs($this->user())->post('/users/email', ['action' => 'reset', 'ids' => [1]])->assertSessionHasErrors('email');
        $this->resetRoutes();
        config(['auth.passwords.other' => ['provider' => 'other'], 'auth.providers.other.model' => SoftUser::class, 'laravelusers.emails.password_broker' => 'other']);
        $this->post('/users/email', ['action' => 'reset', 'ids' => [1]])->assertSessionHasErrors('email');
        Notification::assertNothingSent();
    }

    public function test_package_expiry_is_enforced_by_a_new_host_broker_instance(): void
    {
        Notification::fake();
        $this->resetRoutes();
        config(['laravelusers.emails.reset_expire' => '15']);
        $otherBroker = config('auth.passwords.users');
        config(['auth.passwords.staff' => $otherBroker]);
        $user = $this->user();
        $this->actingAs($user)->post('/users/email', ['action' => 'reset', 'ids' => [$user->id]])->assertSessionHasNoErrors();
        $this->assertSame($otherBroker, config('auth.passwords.staff'));
        Notification::assertSentOnDemand(ResetUserPassword::class, function ($notification) use ($user) {
            $this->assertSame(15, $notification->minutes);
            $this->assertContains(trans('laravelusers::ui.reset_expiry', ['minutes' => 15]), $notification->toMail($user)->outroLines);
            $token = basename(parse_url($notification->url, PHP_URL_PATH));
            Password::clearResolvedInstance('auth.password');
            $this->app->forgetInstance('auth.password');
            $broker = Password::broker();
            $this->travel(14)->minutes();
            $this->assertTrue($broker->tokenExists($user, $token));
            $this->travel(2)->minutes();
            $result = $broker->reset(['email' => $user->email, 'token' => $token, 'password' => 'replacement123', 'password_confirmation' => 'replacement123'], fn () => $this->fail('Expired token must not reset the password.'));
            $this->assertSame(Password::INVALID_TOKEN, $result);
            $this->assertTrue(Hash::check('password', $user->fresh()->password));
            $this->travelBack();

            return true;
        });
    }

    public function test_unset_or_invalid_package_expiry_preserves_the_host_setting(): void
    {
        foreach ([null, 0, -5, 'invalid', '1.5'] as $minutes) {
            config(['laravelusers.emails.reset_expire' => $minutes, 'auth.passwords.users.expire' => 90]);
            Password::clearResolvedInstance('auth.password');
            $this->app->forgetInstance('auth.password');
            Password::broker();
            $this->assertSame(90, config('auth.passwords.users.expire'));
        }
    }

    public function test_per_email_minutes_are_enforced_without_changing_the_default(): void
    {
        Notification::fake();
        $this->resetRoutes();
        $user = $this->user();
        $default = config('auth.passwords.users.expire');
        $this->actingAs($user)->post('/users/email', ['action' => 'reset', 'ids' => [$user->id], 'reset_duration' => 5, 'reset_unit' => 'minutes'])->assertSessionHasNoErrors();
        $this->assertSame($default, config('auth.passwords.users.expire'));
        Notification::assertSentOnDemand(ResetUserPassword::class, function ($notification) use ($user) {
            $this->assertSame(5, $notification->minutes);
            $token = basename(parse_url($notification->url, PHP_URL_PATH));
            $this->assertStringStartsWith('lu1.', $token);
            Password::clearResolvedInstance('auth.password');
            $this->app->forgetInstance('auth.password');
            $broker = Password::broker();
            $this->assertFalse($broker->tokenExists($user, substr($token, 4)));
            $tampered = $token;
            $tampered[50] = $tampered[50] === 'a' ? 'b' : 'a';
            $this->assertFalse($broker->tokenExists($user, $tampered));
            $this->travel(4)->minutes();
            $this->assertTrue($broker->tokenExists($user, $token));
            $this->travel(2)->minutes();
            $this->assertFalse($broker->tokenExists($user, $token));
            $this->travelBack();

            return true;
        });
    }

    public function test_per_email_days_survive_native_cleanup_and_expire_at_the_chosen_time(): void
    {
        Notification::fake();
        $this->resetRoutes();
        config(['auth.passwords.users.expire' => 5]);
        $user = $this->user();
        $this->actingAs($user)->post('/users/email', ['action' => 'reset', 'ids' => [$user->id], 'reset_duration' => 2, 'reset_unit' => 'days'])->assertSessionHasNoErrors();
        $this->assertSame(5, config('auth.passwords.users.expire'));
        Notification::assertSentOnDemand(ResetUserPassword::class, function ($notification) use ($user) {
            $this->assertSame(2880, $notification->minutes);
            $token = basename(parse_url($notification->url, PHP_URL_PATH));
            Password::clearResolvedInstance('auth.password');
            $this->app->forgetInstance('auth.password');
            $broker = Password::broker();
            $this->travel(47)->hours();
            $broker->getRepository()->deleteExpired();
            $this->assertTrue($broker->tokenExists($user, $token));
            $this->travel(2)->hours();
            $this->assertFalse($broker->tokenExists($user, $token));
            $this->travelBack();

            return true;
        });
    }

    public function test_per_email_hours_reset_through_the_host_broker_only_once(): void
    {
        Notification::fake();
        $this->resetRoutes();
        $user = $this->user();
        $this->actingAs($user)->post('/users/email', ['action' => 'reset', 'ids' => [$user->id], 'reset_duration' => 2, 'reset_unit' => 'hours'])->assertSessionHasNoErrors();
        Notification::assertSentOnDemand(ResetUserPassword::class, function ($notification) use ($user) {
            $this->assertSame(120, $notification->minutes);
            $token = basename(parse_url($notification->url, PHP_URL_PATH));
            $this->travel(90)->minutes();
            $result = Password::broker()->reset(['email' => $user->email, 'token' => $token, 'password' => 'replacement123', 'password_confirmation' => 'replacement123'], function ($user, $password) {
                $user->password = Hash::make($password);
                $user->save();
            });
            $this->assertSame(Password::PASSWORD_RESET, $result);
            $this->assertTrue(Hash::check('replacement123', $user->fresh()->password));
            $this->assertFalse(Password::broker()->tokenExists($user, $token));
            $this->travelBack();

            return true;
        });
    }

    public function test_invalid_or_disabled_reset_durations_send_nothing(): void
    {
        Notification::fake();
        $this->resetRoutes();
        $user = $this->user();
        $this->actingAs($user);
        $data = ['action' => 'reset', 'ids' => [$user->id]];
        foreach ([['reset_duration' => 0, 'reset_unit' => 'minutes'], ['reset_duration' => 31, 'reset_unit' => 'days'], ['reset_duration' => 2], ['reset_duration' => 2, 'reset_unit' => ['hours']], ['reset_duration' => [1], 'reset_unit' => 'hours']] as $invalid) {
            $this->post('/users/email', array_merge($data, $invalid))->assertSessionHasErrors();
        }
        config(['laravelusers.emails.reset_duration' => false]);
        $this->post('/users/email', array_merge($data, ['reset_duration' => 2, 'reset_unit' => 'hours']))->assertSessionHasErrors('reset_duration');
        Notification::assertNothingSent();
    }

    public function test_never_expiring_reset_links_survive_cleanup_but_are_single_use(): void
    {
        Notification::fake();
        $this->resetRoutes();
        $user = $this->user();
        $this->actingAs($user)->post('/users/email', ['action' => 'reset', 'ids' => [$user->id], 'reset_never_expire' => 1])->assertSessionHasNoErrors();
        Notification::assertSentOnDemand(ResetUserPassword::class, function ($notification) use ($user) {
            $token = basename(parse_url($notification->url, PHP_URL_PATH));
            $this->assertSame(0, $notification->minutes);
            $this->assertStringContainsString('does not expire', implode(' ', $notification->toMail($user)->outroLines));
            $this->travel(10)->years();

            try {
                $this->app->forgetInstance('auth.password');
                Password::clearResolvedInstance('auth.password');
                Password::broker()->getRepository()->deleteExpired();
                $this->assertTrue(Password::broker()->tokenExists($user, $token));
                $this->assertFalse(Password::broker()->tokenExists($user, 'lu1.'.str_repeat('a', 4097)));
                config(['laravelusers.emails.reset_allow_never_expire' => false]);
                $this->assertFalse(Password::broker()->tokenExists($user, $token));
                config(['laravelusers.emails.reset_allow_never_expire' => true]);
                Password::broker()->deleteToken($user);
                $this->assertFalse(Password::broker()->tokenExists($user, $token));
            } finally {
                $this->travelBack();
            }

            return true;
        });
    }

    public function test_cache_reset_links_keep_the_chosen_duration_and_support_never_expiring_links(): void
    {
        if (!class_exists(CacheTokenRepository::class)) {
            $this->markTestSkipped('Cache password brokers are not provided by this Laravel version.');
        }
        Notification::fake();
        $this->resetRoutes();
        config(['auth.passwords.users.driver' => 'cache', 'auth.passwords.users.store' => 'array', 'auth.passwords.users.throttle' => 0]);
        $this->app->forgetInstance('auth.password');
        Password::clearResolvedInstance('auth.password');
        $user = $this->user();
        $this->actingAs($user)->post('/users/email', ['action' => 'reset', 'ids' => [$user->id], 'reset_duration' => 2, 'reset_unit' => 'hours'])->assertSessionHasNoErrors();
        Notification::assertSentOnDemand(ResetUserPassword::class, function ($notification) use ($user) {
            $token = basename(parse_url($notification->url, PHP_URL_PATH));
            $this->travel(61)->minutes();
            $this->assertTrue(Password::broker()->tokenExists($user, $token));
            $this->travel(60)->minutes();
            $this->assertFalse(Password::broker()->tokenExists($user, $token));
            $this->travelBack();

            return true;
        });
        Notification::fake();
        $this->post('/users/email', ['action' => 'reset', 'ids' => [$user->id], 'reset_never_expire' => 1])->assertSessionHasNoErrors();
        Notification::assertSentOnDemand(ResetUserPassword::class, function ($notification) use ($user) {
            $token = basename(parse_url($notification->url, PHP_URL_PATH));
            $this->travel(10)->years();
            $this->assertTrue(Password::broker()->tokenExists($user, $token));
            Password::broker()->deleteToken($user);
            $this->assertFalse(Password::broker()->tokenExists($user, $token));
            $this->travelBack();

            return true;
        });
    }

    public function test_welcome_and_reset_contents_are_editable_and_use_the_same_preview_templates(): void
    {
        Notification::fake();
        $this->resetRoutes();
        $user = $this->user(['name' => 'Sam']);
        $this->actingAs($user);
        foreach (['welcome' => WelcomeUser::class, 'reset' => ResetUserPassword::class] as $action => $class) {
            $data = $this->message(['action' => $action, 'ids' => [$user->id], 'subject' => 'A personal notice', 'message' => '<script>alert(1)</script>', 'greeting' => 'Hello', 'signoff' => 'Best wishes']);
            $preview = $this->postJson('/users/email/preview', $data)->assertOk()->json('html');
            $this->assertStringContainsString('Hello Sam,', $preview);
            $this->assertStringContainsString('&lt;script&gt;', $preview);
            $this->assertStringNotContainsString('<script>', $preview);
            $this->post('/users/email', $data)->assertSessionHasNoErrors();
            Notification::assertSentOnDemand($class, function ($notification) use ($user, $action) {
                $mail = $notification->toMail($user);
                $this->assertSame('A personal notice', $mail->subject);
                $html = (string) $this->app->make(Markdown::class)->render($mail->markdown, $mail->data());
                $this->assertStringContainsString('Best wishes', $html);
                $this->assertStringContainsString('Hello Sam,', $html);
                $this->assertStringNotContainsString('<script>', $html);
                $this->assertStringContainsString($action === 'reset' ? 'Reset password' : 'Sign in', $html);
                if ($action === 'reset') {
                    $this->assertStringContainsString('60 minutes', $html);
                }

                return true;
            });
            config(['laravelusers.emails.edit_'.$action => false]);
            $this->post('/users/email', $data)->assertSessionHasErrors('message');
            $this->postJson('/users/email/preview', $data)->assertUnprocessable();
        }
    }

    public function test_bulk_and_never_expiring_email_options_can_be_disabled_independently(): void
    {
        Notification::fake();
        $one = $this->user();
        $two = $this->user();
        config(['laravelusers.bulkActions' => true, 'laravelusers.emails.bulk' => false, 'laravelusers.emails.reset_allow_never_expire' => false]);
        $this->actingAs($one)->post('/users/email', $this->message(['ids' => [$one->id, $two->id]]))->assertSessionHasErrors('ids');
        $this->post('/users/email', ['action' => 'reset', 'ids' => [$one->id], 'reset_never_expire' => 1])->assertSessionHasErrors('reset_never_expire');
        Notification::assertNothingSent();
        $this->post('/users/email', $this->message(['ids' => [$one->id]]))->assertSessionHasNoErrors();
    }

    public function test_email_send_and_preview_share_the_configured_request_limit(): void
    {
        Notification::fake();
        $user = $this->user();
        $this->actingAs($user);
        for ($index = 0; $index < 10; $index++) {
            $this->postJson('/users/email/preview', $this->message())->assertOk();
        }
        $this->postJson('/users/email/preview', $this->message())->assertStatus(429);
        $this->post('/users/email', $this->message())->assertStatus(429);
        Notification::assertNothingSent();
    }

    public function test_queue_failure_reports_the_number_already_queued(): void
    {
        $this->mock(Dispatcher::class, function ($mock) {
            $mock->shouldReceive('send')->once()->ordered()->andReturnNull();
            $mock->shouldReceive('send')->once()->ordered()->andThrow(new RuntimeException('Queue unavailable'));
        });
        $this->mock(ExceptionHandler::class, fn ($mock) => $mock->shouldReceive('report')->once());
        $owner = $this->user();
        $other = $this->user();
        config(['laravelusers.bulkActions' => true]);
        $this->actingAs($owner);

        try {
            $this->app->make(EmailUsers::class)->handle($this->message(['ids' => [$owner->id, $other->id]]));
            $this->fail('Expected email dispatch to fail.');
        } catch (ValidationException $exception) {
            $this->assertSame(['email' => [trans_choice('laravelusers::ui.email_failed', 1, ['count' => 1])]], $exception->errors());
        }
    }
}

class EmailUserPolicy
{
    public function update(User $actor, User $recipient): bool
    {
        return $actor->id === $recipient->id;
    }
}
