<?php

namespace jeremykenedy\laravelusers\Test\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Mail\Markdown;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use jeremykenedy\laravelusers\Models\AccountLink;
use jeremykenedy\laravelusers\Notifications\UserMessage;
use jeremykenedy\laravelusers\Support\AccountLinks;
use jeremykenedy\laravelusers\Test\Fixtures\SoftUser;
use jeremykenedy\laravelusers\Test\TestCase;
use RuntimeException;

class AccountLinksTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['laravelusers.emails.enabled' => true, 'laravelusers.welcome.enabled' => true]);
        Schema::table('users', fn (Blueprint $table) => $table->softDeletes());
        config(['laravelusers.defaultUserModel' => SoftUser::class, 'laravelusers.softDeletedEnabled' => true, 'laravelusers.bulkActions' => true, 'laravelusers.account_links.enabled' => true]);
        (require dirname(__DIR__, 2).'/src/database/account-links/2026_10_08_000000_create_laravelusers_account_links_table.php')->up();
    }

    private function deletedUser(): SoftUser
    {
        $user = SoftUser::findOrFail($this->user()->id);
        $user->delete();

        return $user;
    }

    private function issue(SoftUser $user, array $actions = ['restore', 'force_delete'], int $minutes = 60): array
    {
        return $this->app->make(AccountLinks::class)->issue($user, $actions, $minutes);
    }

    private function message(array $extra = []): array
    {
        return array_merge(['action' => 'message', 'deleted' => 1, 'ids' => [2], 'subject' => 'Your account', 'message' => 'You can choose what happens to your account.', 'include_restore' => 1, 'include_force_delete' => 1, 'account_duration' => 2, 'account_unit' => 'hours'], $extra);
    }

    public function test_links_require_confirmation_then_are_consumed_together(): void
    {
        $user = $this->deletedUser();
        $urls = $this->issue($user);
        $this->get($urls['restore'])->assertOk()->assertSee('Restore my account')->assertHeader('Cache-Control', 'no-store, private')->assertHeader('Referrer-Policy', 'no-referrer');
        $this->assertTrue($user->fresh()->trashed());
        $this->assertSame(0, AccountLink::whereNotNull('consumed_at')->count());
        $this->post($urls['restore'])->assertOk()->assertSee('Your account has been restored');
        $this->assertFalse($user->fresh()->trashed());
        $this->assertSame(2, AccountLink::whereNotNull('consumed_at')->count());
        foreach ($urls as $url) {
            $this->get($url)->assertStatus(410)->assertSee('This account link is invalid');
            $this->post($url)->assertStatus(410);
        }
        $user->fresh()->delete();
        $this->post($urls['force_delete'])->assertStatus(410);
        $this->assertNotNull($user->fresh());
    }

    public function test_permanent_delete_is_scoped_to_the_exact_deleted_account(): void
    {
        $one = $this->deletedUser();
        $two = $this->deletedUser();
        $urls = $this->issue($one);
        $this->get($urls['force_delete'])->assertOk()->assertSee('cannot be undone');
        $this->assertNotNull($one->fresh());
        $this->post($urls['force_delete'], ['user_id' => $two->id, 'action' => 'restore'])->assertOk()->assertSee('Your account has been permanently deleted');
        $this->assertNull($one->fresh());
        $this->assertTrue($two->fresh()->trashed());
        $this->post($urls['restore'])->assertStatus(410);
    }

    public function test_tampered_wrong_secret_and_expired_links_cannot_change_accounts(): void
    {
        $user = $this->deletedUser();
        $url = $this->issue($user, ['restore'], 5)['restore'];
        $token = basename($url);
        $tampered = $token;
        $tampered[40] = $tampered[40] === 'a' ? 'b' : 'a';
        $credentials = json_decode($this->app['encrypter']->decrypt(strtr($token, '-_', '+/'), false), true);
        $this->assertSame(hash('sha256', $credentials['secret']), AccountLink::first()->token_hash);
        $this->assertStringNotContainsString($credentials['secret'], json_encode(AccountLink::first()->getAttributes()));
        $credentials['secret'] = str_repeat('a', 64);
        $wrong = rtrim(strtr($this->app['encrypter']->encrypt(json_encode($credentials), false), '+/', '-_'), '=');
        foreach ([$tampered, $wrong, 'invalid', str_repeat('a', 4097)] as $invalid) {
            $this->get(route('users.account-link', ['token' => $invalid]))->assertStatus(410);
            $this->post(route('users.account-link', ['token' => $invalid]))->assertStatus(410);
        }
        $this->travel(5)->minutes();
        $this->get($url)->assertStatus(410);
        $this->post($url)->assertStatus(410);
        $this->assertTrue($user->fresh()->trashed());
        $this->travelBack();
    }

    public function test_administrator_restore_and_external_changes_invalidate_old_links(): void
    {
        $admin = $this->user();
        $user = $this->deletedUser();
        $url = $this->issue($user, ['restore'])['restore'];
        $this->actingAs($admin)->post('/users/'.$user->id.'/restore')->assertRedirect();
        $user->fresh()->delete();
        $this->post($url)->assertStatus(410);
        $new = $this->issue($user->fresh(), ['restore'])['restore'];
        $user->fresh()->update(['email' => 'changed@example.com']);
        $this->post($new)->assertStatus(410);
        $this->assertTrue($user->fresh()->trashed());
        $another = $this->issue($user->fresh(), ['restore'])['restore'];
        $user->fresh()->restore();
        $user->fresh()->delete();
        $this->post($another)->assertStatus(410);
    }

    public function test_failed_account_action_rolls_back_consumption(): void
    {
        $user = $this->deletedUser();
        $url = $this->issue($user, ['force_delete'])['force_delete'];
        $this->app['events']->listen('eloquent.deleting: '.SoftUser::class, fn () => false);
        $this->withoutExceptionHandling();

        try {
            $this->post($url);
            $this->fail('Expected the account action to fail.');
        } catch (RuntimeException $exception) {
            $this->assertSame('The account action could not be completed.', $exception->getMessage());
        }
        $this->assertNull(AccountLink::first()->consumed_at);
        $this->assertTrue($user->fresh()->trashed());
    }

    public function test_custom_deleted_email_has_personalized_laravel_templates_and_expiry(): void
    {
        Notification::fake();
        $admin = $this->user();
        $one = $this->deletedUser();
        $two = $this->deletedUser();
        $this->actingAs($admin)->from('/users/deleted')->post('/users/email', $this->message(['ids' => [$one->id, $two->id], 'message' => '<img src=x onerror=alert(1)>', 'use_greeting' => 1, 'greeting' => 'Hi', 'include_name' => 1]))->assertRedirect('/users/deleted')->assertSessionHasNoErrors();
        $urls = [];
        Notification::assertSentOnDemand(UserMessage::class, function ($notification, $channels, $recipient) use (&$urls) {
            $this->assertSame(['mail'], $channels);
            $mail = $notification->toMail($recipient);
            $this->assertSame('laravelusers::emails.deleted-user', $mail->markdown);
            $this->assertSame(120, $mail->viewData['accountMinutes']);
            $markdown = $this->app->make(Markdown::class);
            $html = (string) $markdown->render($mail->markdown, $mail->data());
            $text = (string) $markdown->renderText($mail->markdown, $mail->data());
            $this->assertStringNotContainsString('<img src=x', $html);
            $this->assertStringContainsString('&lt;img', $html);
            $this->assertStringContainsString('Restore my account', $html);
            $this->assertStringContainsString('Permanently delete my account', $text);
            $this->assertStringContainsString('120 minutes', $text);
            $urls[] = $notification->accountLinks['restore'];

            return true;
        });
        $this->assertCount(2, array_unique($urls));
        $this->assertSame(4, AccountLink::count());
        $this->assertTrue($one->fresh()->trashed());
    }

    public function test_disabled_options_invalid_durations_and_active_users_send_no_links(): void
    {
        $this->withoutMiddleware(ThrottleRequests::class);
        Notification::fake();
        $admin = $this->user();
        $user = $this->deletedUser();
        $this->actingAs($admin);
        foreach ([['deleted' => 0], ['action' => 'welcome'], ['account_duration' => 0], ['account_duration' => 31, 'account_unit' => 'days'], ['account_unit' => ['days']], ['account_duration' => [1]], ['ids' => [$admin->id]], ['ids' => [$user->id, $admin->id]]] as $extra) {
            $this->post('/users/email', $this->message($extra))->assertSessionHasErrors();
        }
        config(['laravelusers.account_links.force_delete' => false]);
        $this->post('/users/email', $this->message())->assertSessionHasErrors('include_force_delete');
        config(['laravelusers.account_links.enabled' => false]);
        $this->post('/users/email', $this->message())->assertSessionHasErrors('include_restore');
        $this->assertSame(0, AccountLink::count());
        Notification::assertNothingSent();
        $this->post('/users/email', $this->message(['include_restore' => 0, 'include_force_delete' => 0, 'account_duration' => null, 'account_unit' => null]))->assertSessionHasNoErrors();
        Notification::assertSentOnDemand(UserMessage::class, fn ($notification) => $notification->accountLinks === []);
    }

    public function test_missing_migration_fails_closed_and_expired_records_can_be_pruned(): void
    {
        $user = $this->deletedUser();
        $urls = $this->issue($user);
        $this->travel(61)->minutes();
        $this->artisan('laravelusers:prune-account-links')->expectsOutput('2 expired account links removed.')->assertExitCode(0);
        $this->travelBack();
        $this->get($urls['restore'])->assertStatus(410);
        Schema::drop('laravelusers_account_links');
        Notification::fake();
        $this->actingAs($this->user())->post('/users/email', $this->message(['ids' => [$user->id]]))->assertSessionHasErrors('account_duration');
        Notification::assertNothingSent();
        $this->get($urls['restore'])->assertStatus(410);
    }

    public function test_never_expiring_account_links_remain_bound_to_the_account_and_single_use(): void
    {
        $user = $this->deletedUser();
        $urls = $this->issue($user, ['restore', 'force_delete'], 0);
        $this->assertNull(AccountLink::first()->expires_at);
        $this->travel(10)->years();

        try {
            $this->artisan('laravelusers:prune-account-links')->expectsOutput('0 expired account links removed.')->assertExitCode(0);
            $this->get($urls['restore'])->assertOk();
            config(['laravelusers.account_links.allow_never_expire' => false]);
            $this->post($urls['restore'])->assertStatus(410);
            config(['laravelusers.account_links.allow_never_expire' => true]);
            $this->post($urls['restore'])->assertOk();
            $this->assertFalse($user->fresh()->trashed());
            $this->post($urls['force_delete'])->assertStatus(410);
        } finally {
            $this->travelBack();
        }
    }

    public function test_never_expiring_choice_is_validated_in_deleted_email_requests(): void
    {
        Notification::fake();
        $admin = $this->user();
        $user = $this->deletedUser();
        $data = $this->message(['ids' => [$user->id], 'account_never_expire' => 1, 'account_duration' => null, 'account_unit' => null]);
        $this->actingAs($admin)->post('/users/email', $data)->assertSessionHasNoErrors();
        $this->assertSame(2, AccountLink::whereNull('expires_at')->count());
        Notification::assertSentOnDemand(UserMessage::class, function ($notification) {
            $mail = $notification->toMail(null);
            $this->assertSame(0, $mail->viewData['accountMinutes']);
            $html = (string) $this->app->make(Markdown::class)->render($mail->markdown, $mail->data());
            $this->assertStringContainsString('do not expire', $html);

            return true;
        });
        config(['laravelusers.account_links.allow_never_expire' => false]);
        $this->post('/users/email', $data)->assertSessionHasErrors('account_never_expire');
        $this->assertSame(2, AccountLink::count());
    }

    public function test_public_confirmation_requires_csrf_and_has_a_separate_rate_limit(): void
    {
        $user = $this->deletedUser();
        $url = $this->issue($user, ['restore'])['restore'];
        $this->app->instance('env', 'local');
        $this->post($url)->assertStatus(419);
        $this->assertTrue($user->fresh()->trashed());
        $this->app->instance('env', 'testing');
        for ($index = 0; $index < 20; $index++) {
            $this->get($url)->assertOk();
        }
        $this->get($url)->assertStatus(429);
        $this->assertTrue($user->fresh()->trashed());
    }

    public function test_simultaneous_requests_can_consume_a_link_only_once(): void
    {
        if (!function_exists('pcntl_fork')) {
            $this->markTestSkipped('Concurrent consumption requires pcntl.');
        }
        $user = $this->deletedUser();
        $url = $this->issue($user, ['restore'])['restore'];
        $directory = sys_get_temp_dir().'/account-links-'.bin2hex(random_bytes(8));
        mkdir($directory, 0700);
        $database = $directory.'/database.sqlite';
        $this->app['db']->connection()->statement('VACUUM INTO ?', [$database]);
        config(['database.connections.testing.database' => $database]);
        $this->app['db']->purge('testing');
        $children = [];

        try {
            foreach ([0, 1] as $index) {
                $pid = pcntl_fork();
                if ($pid === -1) {
                    throw new RuntimeException('Could not start the concurrent request.');
                }
                if ($pid === 0) {
                    $this->app['db']->purge('testing');
                    while (!file_exists($directory.'/start')) {
                        usleep(1000);
                    }

                    try {
                        $result = $this->app->make(AccountLinks::class)->consume(basename($url));
                        file_put_contents($directory.'/'.$index, json_encode($result));
                        exit(0);
                    } catch (\Throwable $exception) {
                        file_put_contents($directory.'/'.$index, $exception->getMessage());
                        exit(1);
                    }
                }
                $children[] = $pid;
            }
            touch($directory.'/start');
            $statuses = [];
            foreach ($children as $index => $child) {
                pcntl_waitpid($child, $status);
                $statuses[$index] = pcntl_wexitstatus($status);
            }
            foreach ($statuses as $index => $status) {
                $this->assertSame(0, $status, (string) file_get_contents($directory.'/'.$index));
            }
            $results = [json_decode(file_get_contents($directory.'/0'), true), json_decode(file_get_contents($directory.'/1'), true)];
            $this->assertEqualsCanonicalizing(['restore', null], $results);
            $this->assertSame(1, AccountLink::whereNotNull('consumed_at')->count());
            $this->assertFalse($user->fresh()->trashed());
        } finally {
            $this->app['db']->purge('testing');
            foreach (glob($directory.'/*') as $file) {
                unlink($file);
            }
            rmdir($directory);
        }
    }

    public function test_links_and_deleted_emails_respect_the_configuration_and_all_frameworks(): void
    {
        $admin = $this->user();
        $user = $this->deletedUser();
        $url = $this->issue($user, ['restore'])['restore'];
        config(['laravelusers.account_links.enabled' => false]);
        $this->get($url)->assertStatus(410);
        $this->actingAs($admin);
        foreach (['bootstrap4', 'bootstrap5', 'tailwind'] as $framework) {
            config(['laravelusers.frontend' => $framework]);
            $this->get('/users/deleted')->assertOk()->assertSee('data-lu-email-deleted="1"', false)->assertSee('value="message"', false);
            config(['laravelusers.emails.deleted' => false]);
            $this->get('/users/deleted')->assertOk()->assertDontSee('data-lu-email-deleted="1"', false);
            config(['laravelusers.emails.deleted' => true]);
        }
    }
}
