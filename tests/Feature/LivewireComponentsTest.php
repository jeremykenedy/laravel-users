<?php

namespace jeremykenedy\laravelusers\Test\Feature;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use jeremykenedy\laravelusers\Test\TestCase;

class LivewireComponentsTest extends TestCase
{
    public function test_fields_connect_server_errors_to_the_bound_input_and_escape_messages(): void
    {
        $errors = (new ViewErrorBag())->put('default', new MessageBag(['form.email' => '<script>Invalid address</script>']));
        $html = Blade::render('<x-laravelusers::livewire.field name="email" model="form.email" label="Email" type="email" id="native-email" help="Use an account address" :errors="$errors" />', compact('errors'));

        $this->assertStringContainsString('wire:model="form.email"', $html);
        $this->assertStringContainsString('aria-invalid="true"', $html);
        $this->assertStringContainsString('aria-describedby="native-email-help native-email-error"', $html);
        $this->assertStringContainsString('&lt;script&gt;Invalid address&lt;/script&gt;', $html);
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertSame(1, substr_count($html, 'id="native-email"'));
    }

    public function test_role_choices_support_multiple_values_and_escape_their_labels(): void
    {
        $options = [['value' => '3', 'label' => '<img src=x onerror=alert(1)>'], ['value' => '4', 'label' => 'Editor', 'disabled' => true]];
        $html = Blade::render('<x-laravelusers::livewire.field name="role[]" model="form.role" label="Roles" type="select" :options="$options" :multiple="true" />', compact('options'));

        $this->assertStringContainsString('name="role[]"', $html);
        $this->assertStringContainsString('multiple', $html);
        $this->assertStringContainsString('&lt;img src=x onerror=alert(1)&gt;', $html);
        $this->assertStringNotContainsString('<img ', $html);
        $this->assertMatchesRegularExpression('/<option value="4"\s+disabled/', $html);
    }

    public function test_confirmation_dialog_can_disable_a_destructive_submission(): void
    {
        $html = Blade::render('<x-laravelusers::livewire.dialog id="delete-user" title="Delete user" confirm="confirmAction" confirm-label="Delete" :danger="true" :confirm-disabled="true">Enter the confirmation text.</x-laravelusers::livewire.dialog>');

        $this->assertStringContainsString('role="dialog" aria-modal="true" aria-labelledby="delete-user-title"', $html);
        $this->assertStringContainsString('wire:click="confirmAction"', $html);
        $this->assertMatchesRegularExpression('/wire:loading.attr="disabled"\s+disabled/', $html);
        $this->assertStringContainsString('wire:click="closeDialog"', $html);
    }

    public function test_avatar_and_login_details_escape_user_data_and_preserve_private_image_requests(): void
    {
        $avatar = ['fallback' => 'initials', 'initials' => '<JR>', 'size' => 40, 'src' => 'https://example.com/avatar.png'];
        $activity = ['online' => true, 'last_login_at' => '2026-10-08T12:00:00Z', 'browser' => '<script>Browser</script>'];
        $html = Blade::render('<x-laravelusers::livewire.avatar :avatar="$avatar" name="Jordan Rivers" /><x-laravelusers::livewire.activity :activity="$activity" />', compact('avatar', 'activity'));

        $this->assertStringContainsString('aria-label="Jordan Rivers"', $html);
        $this->assertStringContainsString('&lt;JR&gt;', $html);
        $this->assertStringContainsString('referrerpolicy="no-referrer"', $html);
        $this->assertStringContainsString('datetime="2026-10-08T12:00:00Z"', $html);
        $this->assertStringContainsString('&lt;script&gt;Browser&lt;/script&gt;', $html);
        $this->assertStringNotContainsString('<script>', $html);
    }
}
