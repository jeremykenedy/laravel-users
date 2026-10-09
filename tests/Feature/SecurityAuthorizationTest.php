<?php

namespace jeremykenedy\laravelusers\Test\Feature;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use jeremykenedy\laravelusers\Jobs\ChangeManagedPackage;
use jeremykenedy\laravelusers\Notifications\UserMessage;
use jeremykenedy\laravelusers\Support\ComposerPackages;
use jeremykenedy\laravelusers\Support\ImpersonationSession;
use jeremykenedy\laravelusers\Support\ManagedPackages;
use jeremykenedy\laravelusers\Support\UserSettings;
use jeremykenedy\laravelusers\Test\TestCase;

/**
 * Integration fixtures exercise the framework types and optional providers used by this feature.
 *
 * @SuppressWarnings("PHPMD.CouplingBetweenObjects")
 */
class SecurityAuthorizationTest extends TestCase
{
    public function test_configured_automatic_goodbye_remains_available_without_email_permission(): void
    {
        Notification::fake();
        config([
            'laravelusers.settings.enabled'          => true,
            'laravelusers.emails.enabled'            => true,
            'laravelusers.emails.goodbye'            => true,
            'laravelusers.emails.goodbye_auto_send'  => true,
            'laravelusers.emails.goodbye_subject'    => 'Configured subject',
            'laravelusers.emails.goodbye_message'    => 'Configured message',
            'laravelusers.access.email_goodbye.mode' => 'deny',
        ]);
        $actor = $this->user();
        $recipient = $this->user();

        $this->actingAs($actor)->delete('/users/'.$recipient->id)->assertRedirect('/users');

        Notification::assertSentOnDemand(UserMessage::class, fn ($notification) => $notification->contents['subject'] === 'Configured subject'
            && $notification->contents['message'] === 'Configured message');
        $this->assertNull($recipient->fresh());
    }

    public function test_automatic_goodbye_rejects_custom_content_without_email_permission(): void
    {
        Notification::fake();
        config([
            'laravelusers.settings.enabled'          => true,
            'laravelusers.emails.enabled'            => true,
            'laravelusers.emails.goodbye'            => true,
            'laravelusers.emails.goodbye_auto_send'  => true,
            'laravelusers.access.email_goodbye.mode' => 'deny',
        ]);
        $actor = $this->user();
        $recipient = $this->user();

        $this->actingAs($actor)->deleteJson('/users/'.$recipient->id, [
            'goodbye' => ['subject' => 'Unauthorized subject', 'message' => 'Unauthorized message'],
        ])->assertUnprocessable();

        Notification::assertNothingSent();
        $this->assertNotNull($recipient->fresh());
    }

    public function test_package_worker_rechecks_management_gate_after_enqueueing(): void
    {
        $cachePath = sys_get_temp_dir().'/laravelusers-security-'.bin2hex(random_bytes(8));
        config([
            'laravelusers.settings.enabled'          => true,
            'laravelusers.settings.packages.enabled' => true,
            'laravelusers.middleware'                => ['can:manage-users'],
            'queue.default'                          => 'database',
            'queue.connections.database.driver'      => 'database',
            'queue.connections.database.retry_after' => 600,
            'cache.default'                          => 'file',
            'cache.stores.file.driver'               => 'file',
            'cache.stores.file.path'                 => $cachePath,
            'cache.stores.file.lock_path'            => $cachePath.'/locks',
        ]);
        Cache::forgetDriver('file');
        Gate::define('manage-users', fn () => true);
        Gate::define('manage-laravelusers-settings', fn () => true);
        Gate::define('manage-laravelusers-packages', fn () => true);
        Bus::fake();
        $packages = \Mockery::mock(ManagedPackages::class)->makePartial();
        $packages->shouldReceive('installed')->andReturnFalse();
        $this->app->instance(ManagedPackages::class, $packages);

        try {
            $this->actingAs($this->user())->postJson('/users/settings/packages', [
                'package' => 'spatie', 'operation' => 'install', 'confirmation' => 'continue', 'acknowledgement' => 1,
            ])->assertStatus(202);
            $job = Bus::dispatched(ChangeManagedPackage::class)->first();
            Gate::define('manage-users', fn () => false);
            $this->get('/users/settings')->assertForbidden();
            $ranComposer = false;
            $composer = \Mockery::mock(ComposerPackages::class);
            $composer->shouldReceive('changeFromSettings')->andReturnUsing(function () use (&$ranComposer) {
                $ranComposer = true;

                return true;
            });

            $job->handle($packages, $composer, new UserSettings());

            $this->assertFalse($ranComposer, 'Composer must not run after the actor loses management authorization.');
            $this->assertSame('failed', Cache::get('laravelusers.package.'.$job->id)['status']);
        } finally {
            File::deleteDirectory($cachePath);
        }
    }

    public function test_impersonation_cannot_restore_an_actor_after_their_password_is_reset(): void
    {
        $actor = $this->user();
        $target = $this->user();
        $this->actingAs($actor);
        $request = Request::create('/users/'.$target->id.'/impersonate', 'POST');
        $request->setLaravelSession($this->app['session']->driver());
        $this->app->make(ImpersonationSession::class)->begin($request, $actor, $target);
        $state = $request->session()->get(ImpersonationSession::KEY);
        $actor->update(['password' => Hash::make('replacement-password'), 'remember_token' => 'replacement-token']);

        $this->withSession([ImpersonationSession::KEY => $state])->post('/users/impersonation/stop')->assertForbidden();

        $this->assertGuest();
    }
}
