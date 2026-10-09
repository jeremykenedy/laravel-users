<?php

namespace jeremykenedy\laravelusers\Test\Feature;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use jeremykenedy\laravelusers\Models\UserSetting;
use jeremykenedy\laravelusers\Support\Avatar;
use jeremykenedy\laravelusers\Test\Fixtures\User;
use jeremykenedy\laravelusers\Test\TestCase;

class AvatarPreviewTest extends TestCase
{
    private function enable(): void
    {
        config(['laravelusers.settings.enabled' => true]);
        (require dirname(__DIR__, 2).'/src/database/settings/2026_10_08_145311_create_laravelusers_settings_table.php')->up();
        Gate::define('manage-laravelusers-settings', fn () => true);
    }

    public function test_avatar_previews_require_authentication_settings_access_and_appearance_permission(): void
    {
        $this->postJson('/users/settings/avatar-preview', ['avatar_source' => 'initials'])->assertUnauthorized();
        $this->actingAs($this->user())->postJson('/users/settings/avatar-preview', ['avatar_source' => 'initials'])->assertForbidden();
        config(['laravelusers.settings.enabled' => true]);
        $this->postJson('/users/settings/avatar-preview', ['avatar_source' => 'initials'])->assertForbidden();
        Gate::define('manage-laravelusers-settings', fn () => true);
        config(['laravelusers.access.edit_appearance.mode' => 'deny']);
        $this->postJson('/users/settings/avatar-preview', ['avatar_source' => 'initials'])->assertForbidden();
        $this->get('/users/settings/avatar-preview/profile')->assertForbidden();
    }

    public function test_preview_changes_use_four_fixed_samples_and_do_not_save_settings_or_users(): void
    {
        $this->enable();
        $actor = $this->user(['name' => 'Private Account', 'email' => 'private@example.com']);
        UserSetting::create(['key' => 'global', 'value' => ['avatar.source' => 'initials', 'profileCardColor' => '#264e36']]);
        $saved = UserSetting::findOrFail('global')->value;
        $this->actingAs($actor);
        Http::preventStrayRequests();
        foreach (['initials', 'ui-avatars', 'avatar'] as $source) {
            $response = $this->postJson('/users/settings/avatar-preview', ['avatar_source' => $source, 'name' => $actor->name, 'ids' => [$actor->id]])->assertOk()->assertHeader('Cache-Control', 'no-store, private');
            $avatars = $response->json('avatars');
            $this->assertSame(['profile', 'edit', 'profile_dark', 'edit_dark'], array_keys($avatars));
            $this->assertCount(4, array_unique(array_column($avatars, 'name')));
            $this->assertCount(4, array_unique(array_column(array_column($avatars, 'avatar'), 'initials')));
            $response->assertDontSee($actor->name)->assertDontSee($actor->email);
            if ($source === 'initials') {
                $this->assertSame([null, null, null, null], array_column(array_column($avatars, 'avatar'), 'src'));
            } else {
                $this->assertCount(4, array_unique(array_column(array_column($avatars, 'avatar'), 'src')));
            }
        }
        $this->assertSame($saved, UserSetting::findOrFail('global')->value);
        $this->assertSame('initials', config('laravelusers.avatar.source'));
        $this->assertSame('Private Account', $actor->fresh()->name);
        $this->assertSame(1, User::count());
        Http::assertNothingSent();
    }

    public function test_selected_sources_follow_existing_remote_opt_ins_and_local_fallbacks(): void
    {
        $this->enable();
        $this->actingAs($this->user());
        config(['laravelusers.avatar.remote_enabled' => false, 'laravelusers.avatar.dicebear.driver' => 'remote', 'laravelusers.avatar.ui_avatars.driver' => 'remote']);
        Http::preventStrayRequests();
        foreach (array_merge(['gravatar', 'dicebear', 'ui-avatars'], Avatar::GRAVATAR_STYLES) as $source) {
            $response = $this->postJson('/users/settings/avatar-preview', ['avatar_source' => $source])->assertOk();
            foreach ($response->json('avatars') as $sample) {
                $this->assertNull($sample['avatar']['src']);
            }
        }
        config(['laravelusers.avatar.remote_enabled' => true]);
        $response = $this->postJson('/users/settings/avatar-preview', ['avatar_source' => 'identicon'])->assertOk();
        $sources = array_column(array_column($response->json('avatars'), 'avatar'), 'src');
        $this->assertCount(4, array_unique($sources));
        foreach ($sources as $source) {
            $this->assertStringStartsWith('https://www.gravatar.com/avatar/', $source);
            $this->assertStringContainsString('d=identicon', $source);
        }
        Http::assertNothingSent();
    }

    public function test_local_sample_images_have_fixed_content_private_headers_and_no_user_lookup(): void
    {
        $this->enable();
        $actor = $this->user(['name' => 'Private Account']);
        $this->actingAs($actor);
        $response = $this->postJson('/users/settings/avatar-preview', ['avatar_source' => 'avatar'])->assertOk();
        $images = [];
        foreach ($response->json('avatars') as $kind => $sample) {
            $this->assertSame('/users/settings/avatar-preview/'.$kind, $sample['avatar']['src']);
            $image = $this->get($sample['avatar']['src'])->assertOk()->assertHeader('Content-Type', 'image/svg+xml')->assertHeader('Cache-Control', 'no-store, private')->assertHeader('X-Content-Type-Options', 'nosniff')->assertHeader('Content-Security-Policy', "default-src 'none'; sandbox");
            $image->assertSee($sample['avatar']['initials'])->assertDontSee($actor->name);
            $images[] = $image->getContent();
        }
        $this->assertCount(4, array_unique($images));
        $this->get('/users/settings/avatar-preview/unknown')->assertNotFound();
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('laravelusers_settings', 0);
    }

    public function test_only_supported_avatar_sources_are_accepted(): void
    {
        $this->enable();
        $this->actingAs($this->user());
        foreach (['https://example.com/avatar', ['initials'], 'unknown', ''] as $source) {
            $this->postJson('/users/settings/avatar-preview', ['avatar_source' => $source])->assertUnprocessable()->assertJsonValidationErrors('avatar_source');
        }
        $this->assertDatabaseCount('laravelusers_settings', 0);
    }
}
