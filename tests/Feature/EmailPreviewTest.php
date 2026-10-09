<?php

namespace jeremykenedy\laravelusers\Test\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use jeremykenedy\laravelusers\Models\AccountLink;
use jeremykenedy\laravelusers\Test\Fixtures\SoftUser;
use jeremykenedy\laravelusers\Test\Fixtures\User;
use jeremykenedy\laravelusers\Test\TestCase;

class EmailPreviewTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['laravelusers.emails.enabled' => true, 'laravelusers.welcome.enabled' => true]);
    }

    public function test_preview_uses_current_fields_without_sending_or_creating_tokens(): void
    {
        Queue::fake();
        Notification::fake();
        Schema::table('users', fn (Blueprint $table) => $table->softDeletes());
        config(['laravelusers.defaultUserModel' => SoftUser::class, 'laravelusers.softDeletedEnabled' => true, 'laravelusers.account_links.enabled' => true]);
        (require dirname(__DIR__, 2).'/src/database/account-links/2026_10_08_000000_create_laravelusers_account_links_table.php')->up();
        $admin = $this->user();
        $user = SoftUser::findOrFail($this->user(['name' => 'PreviewUser'])->id);
        $this->actingAs($admin);
        $message = ['action' => 'message', 'ids' => [$user->id], 'subject' => 'Account notice', 'message' => '<script>alert(1)</script>', 'use_greeting' => 1, 'greeting' => 'Hi', 'include_name' => 1, 'use_signoff' => 1, 'signoff' => 'Thanks', 'signoff_name' => 'Admin'];
        $response = $this->postJson('/users/email/preview', $message)->assertOk()->assertJsonPath('recipient', 'PreviewUser')->assertHeader('Cache-Control', 'no-store, private');
        $this->assertStringContainsString('Hi PreviewUser,', $response->json('html'));
        $this->assertStringNotContainsString('<script>', $response->json('html'));
        $this->assertStringContainsString('&lt;script&gt;', $response->json('html'));
        $this->assertStringContainsString('class="wrapper"', $response->json('html'));
        $this->assertStringContainsString('font-family:', $response->json('html'));
        $this->postJson('/users/email/preview', ['action' => 'reset', 'ids' => [$user->id], 'reset_duration' => 2, 'reset_unit' => 'days'])->assertOk()->assertJsonPath('recipient', 'PreviewUser');
        $this->postJson('/users/email/preview', ['action' => 'welcome', 'ids' => [$user->id]])->assertOk();
        $user->delete();
        $response = $this->postJson('/users/email/preview', array_merge($message, ['deleted' => 1, 'include_restore' => 1, 'include_force_delete' => 1, 'account_duration' => 2, 'account_unit' => 'hours']))->assertOk();
        $this->assertStringContainsString('Restore my account', $response->json('html'));
        $this->assertStringContainsString('Permanently delete my account', $response->json('html'));
        $this->assertStringContainsString('120 minutes', $response->json('html'));
        $this->assertSame(0, AccountLink::count());
        $this->assertTrue($user->fresh()->trashed());
        Queue::assertNothingPushed();
        Notification::assertNothingSent();
    }

    public function test_preview_rejects_unauthorized_disabled_and_invalid_requests(): void
    {
        $data = ['action' => 'message', 'ids' => [1], 'subject' => 'Test', 'message' => 'Test'];
        $this->postJson('/users/email/preview', $data)->assertUnauthorized();
        $actor = $this->user();
        $recipient = $this->user();
        $this->actingAs($actor);
        $this->postJson('/users/email/preview', array_merge($data, ['message' => '']))->assertUnprocessable();
        $this->postJson('/users/email/preview', array_merge($data, ['ids' => [999]]))->assertUnprocessable();
        Gate::policy(User::class, PreviewUserPolicy::class);
        $this->postJson('/users/email/preview', array_merge($data, ['ids' => [$recipient->id]]))->assertForbidden();
        config(['laravelusers.emails.preview' => false]);
        $this->postJson('/users/email/preview', $data)->assertForbidden();
        $this->get('/users')->assertOk()->assertDontSee('type="button" data-lu-email-preview-button', false);
    }
}

class PreviewUserPolicy
{
    public function update(User $actor, User $recipient): bool
    {
        return $actor->id === $recipient->id;
    }
}
