<?php

namespace jeremykenedy\laravelusers\Test\Feature;

use jeremykenedy\laravelusers\Support\Avatar;
use jeremykenedy\laravelusers\Test\TestCase;

class AvatarTest extends TestCase
{
    public function test_avatar_sources_and_safe_fallbacks(): void
    {
        $user = $this->user(['name' => 'Élodie Smith']);
        $avatar = new Avatar();
        $this->assertSame([], $avatar->listing([$user]));
        config(['laravelusers.avatar.enabled' => true]);
        $this->assertSame('ÉS', $avatar->forUser($user)['initials']);
        $this->assertNull($avatar->forUser($user)['src']);
        config(['laravelusers.avatar.source' => 'gravatar']);
        $this->assertSame('https://www.gravatar.com/avatar/'.hash('sha256', $user->email).'?s=256&d=404&r=g', $avatar->forUser($user)['src']);
        config(['laravelusers.avatar.source' => 'avatar', 'laravelusers.avatar.attribute' => 'profile_photo_url']);
        foreach (['https://example.com/avatar.jpg', '/storage/photo.jpg'] as $src) {
            $user->setAttribute('profile_photo_url', $src);
            $this->assertSame($src, $avatar->forUser($user)['src']);
        }
        foreach (['javascript:alert(1)', 'data:image/svg+xml,<svg/>', '//example.com/photo', null] as $src) {
            $user->setAttribute('profile_photo_url', $src);
            $this->assertNull($avatar->forUser($user)['src']);
        }
    }

    public function test_avatar_column_is_optional_and_search_metadata_requires_an_explicit_request(): void
    {
        $user = $this->user(['name' => 'Account']);
        $this->actingAs($user);
        config(['laravelusers.avatar.enabled' => true]);
        foreach (['bootstrap4', 'bootstrap5', 'tailwind'] as $framework) {
            config(['laravelusers.frontend' => $framework]);
            $this->get('/users')->assertOk()->assertSee('class="lu-avatar"', false)->assertSee('Avatar');
        }
        $this->postJson('/search-users', ['user_search_box' => 'Account'])->assertExactJson([$user->toArray()]);
        $this->postJson('/search-users', ['user_search_box' => 'Account', 'include_avatar' => 1])->assertJsonPath('avatars.'.$user->id.'.initials', 'A');
        config(['laravelusers.avatar.enabled' => false]);
        $this->get('/users')->assertOk()->assertDontSee('class="lu-avatar"', false);
    }

    public function test_profile_avatar_can_be_disabled_without_hiding_the_table_column(): void
    {
        $user = $this->user(['name' => 'Profile Person']);
        $this->actingAs($user);
        foreach (['bootstrap4', 'bootstrap5', 'tailwind'] as $framework) {
            config(['laravelusers.frontend' => $framework, 'laravelusers.avatar.enabled' => true, 'laravelusers.showProfileAvatar' => true]);
            $this->get('/users/'.$user->id)->assertOk()->assertSee('class="lu-avatar"', false)->assertSee('PP');
            config(['laravelusers.showProfileAvatar' => false]);
            $this->get('/users/'.$user->id)->assertOk()->assertDontSee('class="lu-avatar"', false)->assertSee('Profile Person');
            $this->get('/users')->assertOk()->assertSee('class="lu-avatar"', false);
        }
    }
}
