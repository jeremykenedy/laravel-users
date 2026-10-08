<?php

namespace jeremykenedy\laravelusers\Test\Feature;

use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Notifications\Dispatcher;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Mail\Markdown;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use jeremykenedy\laravelusers\Notifications\WelcomeUser;
use jeremykenedy\laravelusers\Test\Fixtures\User;
use jeremykenedy\laravelusers\Test\TestCase;

class WelcomeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['laravelusers.emails.enabled' => true, 'laravelusers.welcome.enabled' => true]);
    }

    private function account(array $extra = []): array
    {
        return array_merge(['name' => 'newaccount', 'email' => 'new@example.com', 'password' => 'password123', 'password_confirmation' => 'password123'], $extra);
    }

    public function test_master_email_and_welcome_action_switches_also_disable_creation_mail(): void
    {
        Notification::fake();
        $this->actingAs($this->user());
        foreach (['enabled', 'welcome'] as $setting) {
            config(['laravelusers.emails.'.$setting => false]);
            $this->get('/users/create')->assertOk()->assertDontSee('type="checkbox" name="send_welcome_email"', false);
            $this->post('/users', $this->account(['send_welcome_email' => 1]))->assertSessionHasErrors('send_welcome_email');
            config(['laravelusers.emails.'.$setting => true]);
        }
        Notification::assertNothingSent();
        $this->assertSame(1, User::count());
    }

    public function test_existing_creation_does_not_send_mail_and_only_saves_validated_fields(): void
    {
        Notification::fake();
        $this->actingAs($this->user())->post('/users', $this->account(['id' => 100, 'remember_token' => 'injected']))->assertRedirect('/users');
        $user = User::where('email', 'new@example.com')->firstOrFail();
        $this->assertTrue(Hash::check('password123', $user->password));
        $this->assertNotEquals(100, $user->id);
        $this->assertNull($user->remember_token);
        Notification::assertNothingSent();
    }

    public function test_welcome_email_is_optional_and_never_contains_the_password(): void
    {
        Notification::fake();
        $this->actingAs($this->user())->post('/users', $this->account(['send_welcome_email' => 1]))->assertRedirect('/users');
        Notification::assertSentOnDemand(WelcomeUser::class, function ($notification, $channels, $recipient) {
            $mail = $notification->toMail($recipient);
            $this->assertNull($notification->resetUrl);
            $this->assertEquals('new@example.com', $recipient->routes['mail']);
            $this->assertStringNotContainsString('password123', json_encode($mail->toArray()));

            return $channels === ['mail'];
        });
    }

    public function test_reset_requires_welcome_and_can_be_disabled(): void
    {
        Notification::fake();
        $this->actingAs($this->user())->post('/users', $this->account(['force_password_reset' => 1]))->assertSessionHasErrors('send_welcome_email');
        config(['laravelusers.welcome.enabled' => false]);
        $this->post('/users', $this->account(['send_welcome_email' => 1]))->assertSessionHasErrors('send_welcome_email');
        config(['laravelusers.welcome.enabled' => true, 'laravelusers.welcome.force_password_reset' => false]);
        $this->post('/users', $this->account(['send_welcome_email' => 1, 'force_password_reset' => 1]))->assertSessionHasErrors('force_password_reset');
        $this->assertDatabaseMissing('users', ['email' => 'new@example.com']);
        Notification::assertNothingSent();
    }

    public function test_missing_reset_route_does_not_create_an_inaccessible_account(): void
    {
        $this->actingAs($this->user())->post('/users', $this->account(['send_welcome_email' => 1, 'force_password_reset' => 1]))->assertSessionHasErrors('force_password_reset');
        $this->assertDatabaseMissing('users', ['email' => 'new@example.com']);
    }

    public function test_reset_email_issues_a_working_single_use_broker_token(): void
    {
        Notification::fake();
        Route::get('/reset-password/{token}', fn () => 'Reset')->name('password.reset');
        $this->app['router']->getRoutes()->refreshNameLookups();
        $table = config('auth.passwords.users.table');
        Schema::create($table, function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
        $this->actingAs($this->user())->post('/users', $this->account(['password' => null, 'password_confirmation' => null, 'send_welcome_email' => 1, 'force_password_reset' => 1]))->assertRedirect('/users')->assertSessionHasNoErrors();
        $user = User::where('email', 'new@example.com')->firstOrFail();
        $this->assertFalse(Hash::check('password123', $user->password));
        Notification::assertSentOnDemand(WelcomeUser::class, function ($notification, $channels, $recipient) use ($user) {
            parse_str(parse_url($notification->resetUrl, PHP_URL_QUERY), $query);
            $token = basename(parse_url($notification->resetUrl, PHP_URL_PATH));
            $this->assertEquals($user->email, $query['email']);
            $this->assertTrue(Password::broker()->tokenExists($user, $token));
            $this->assertContains(trans('laravelusers::ui.reset_notice'), $notification->toMail($recipient)->introLines);
            $result = Password::broker()->reset(['email' => $user->email, 'token' => $token, 'password' => 'replacement123', 'password_confirmation' => 'replacement123'], function ($user, $password) {
                $user->password = Hash::make($password);
                $user->save();
            });
            $this->assertEquals(Password::PASSWORD_RESET, $result);
            $this->assertTrue(Hash::check('replacement123', $user->fresh()->password));
            $this->assertFalse(Password::broker()->tokenExists($user, $token));

            return true;
        });
    }

    public function test_mail_failure_reports_a_warning_without_rolling_back_the_account(): void
    {
        $this->mock(Dispatcher::class, function ($mock) {
            $mock->shouldReceive('send')->once()->andThrow(new \RuntimeException('Mail unavailable'));
        });
        $this->mock(ExceptionHandler::class, function ($mock) {
            $mock->shouldReceive('report')->once();
        });
        $this->actingAs($this->user())->post('/users', $this->account(['send_welcome_email' => 1]))
            ->assertRedirect('/users')->assertSessionHas('success')->assertSessionHas('error', trans('laravelusers::ui.welcome_failed'));
        $this->assertDatabaseHas('users', ['email' => 'new@example.com']);
    }

    public function test_welcome_template_renders_normal_and_password_setup_emails(): void
    {
        $recipient = $this->user();
        foreach ([null, 'https://example.com/reset-password/token?email=user%40example.com'] as $url) {
            $mail = (new WelcomeUser('<img src=x onerror=alert(1)>', $url))->toMail($recipient);
            $this->assertSame('laravelusers::emails.welcome', $mail->markdown);
            $html = (string) $mail->render();
            $this->assertStringNotContainsString('<img src=x', $html);
            $this->assertStringContainsString('&lt;img src=x', $html);
            $this->assertStringContainsString(trans('laravelusers::ui.welcome_message', ['app' => config('app.name')]), $html);
            $markdown = $this->app->make(Markdown::class);
            $text = (string) $markdown->renderText($mail->markdown, $mail->data());
            $this->assertStringContainsString(trans('laravelusers::ui.welcome_message', ['app' => config('app.name')]), $text);
            if ($url) {
                $this->assertStringContainsString(trans('laravelusers::ui.reset_notice'), $html);
                $this->assertStringContainsString(trans('laravelusers::ui.reset_expiry', ['minutes' => config('auth.passwords.users.expire')]), $text);
            } else {
                $this->assertStringContainsString(route('login'), $html);
                $this->assertStringNotContainsString(trans('laravelusers::ui.reset_notice'), $text);
            }
        }
    }
}
