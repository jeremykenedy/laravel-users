<?php

namespace jeremykenedy\laravelusers\Test\Feature;

use Illuminate\Support\Facades\Http;
use jeremykenedy\laravelusers\Support\Avatar;
use jeremykenedy\laravelusers\Support\AvatarPreferences;
use jeremykenedy\laravelusers\Support\LocalAvatars;
use jeremykenedy\laravelusers\Test\TestCase;

class AvatarProvidersTest extends TestCase
{
    public function test_gravatar_presets_are_explicit_and_keep_existing_gravatar_behavior(): void
    {
        $user = $this->user(['email' => 'test@example.com']);
        $avatar = new Avatar();
        $base = 'https://www.gravatar.com/avatar/'.hash('sha256', 'test@example.com').'?s=256';
        $this->assertSame($base.'&d=404&r=g', $avatar->forUser($user, 'gravatar')['src']);
        foreach (Avatar::GRAVATAR_STYLES as $style) {
            $this->assertSame($base.'&d='.$style.'&r=g&f=y', $avatar->forUser($user, $style)['src']);
        }
        config(['laravelusers.avatar.remote_enabled' => false]);
        foreach (array_merge(['gravatar'], Avatar::GRAVATAR_STYLES) as $source) {
            $this->assertNull($avatar->forUser($user, $source)['src']);
        }
    }

    public function test_ui_avatars_render_locally_with_safe_initials_and_colors(): void
    {
        Http::fake();
        $user = $this->user(['name' => '<svg> Smith']);
        config(['laravelusers.avatar.ui_avatars.background' => '#123abc', 'laravelusers.avatar.ui_avatars.color' => 'white" onload="x']);
        $url = (new Avatar())->forUser($user, 'ui-avatars')['src'];
        $this->assertStringStartsWith('data:image/svg+xml;base64,', $url);
        $svg = base64_decode(substr($url, strpos($url, ',') + 1), true);
        $this->assertStringContainsString('fill="#123abc"', $svg);
        $this->assertStringContainsString('fill="#344760"', $svg);
        $this->assertStringContainsString('&lt;S</text>', $svg);
        $this->assertStringNotContainsString('onload', $svg);
        $this->assertStringNotContainsString($user->email, $svg);
        Http::assertNothingSent();
    }

    public function test_remote_generation_is_opt_in_and_does_not_expose_names_or_emails(): void
    {
        $user = $this->user(['name' => 'Jane Doe']);
        config(['laravelusers.avatar.dicebear.driver' => 'remote', 'laravelusers.avatar.dicebear.url' => 'https://avatars.example.test/10.x', 'laravelusers.avatar.ui_avatars.driver' => 'remote']);
        $avatar = new Avatar();
        $url = $avatar->forUser($user, 'dicebear')['src'];
        $this->assertStringStartsWith('https://avatars.example.test/10.x/identicon/svg?', $url);
        $this->assertStringNotContainsString($user->email, $url);
        $this->assertStringNotContainsString('Jane', $url);
        $this->assertSame($url, $avatar->forUser($user, 'dicebear')['src']);
        $other = $this->user();
        $this->assertNotSame($url, $avatar->forUser($other, 'dicebear')['src']);
        config(['app.key' => 'another-host-secret']);
        $this->assertNotSame($url, $avatar->forUser($user, 'dicebear')['src']);
        $this->assertStringContainsString('name=JD&', $avatar->forUser($user, 'ui-avatars')['src']);
        config(['laravelusers.avatar.remote_enabled' => false]);
        $this->assertNull($avatar->forUser($user, 'dicebear')['src']);
        $this->assertNull($avatar->forUser($user, 'ui-avatars')['src']);
    }

    public function test_invalid_drivers_styles_and_remote_urls_never_become_image_sources(): void
    {
        $avatar = new Avatar();
        $user = $this->user();
        config(['laravelusers.avatar.dicebear.driver' => 'remote', 'laravelusers.avatar.ui_avatars.driver' => 'remote']);
        foreach (['javascript:alert(1)', 'data:image/svg+xml,<svg/>', '//host.test/avatars', 'https://user:pass@host.test/avatars', 'https://host.test/avatars?seed=x', 'https://host.test/avatars#x'] as $url) {
            config(['laravelusers.avatar.dicebear.url' => $url, 'laravelusers.avatar.ui_avatars.url' => $url]);
            $this->assertNull($avatar->forUser($user, 'dicebear')['src']);
            $this->assertNull($avatar->forUser($user, 'ui-avatars')['src']);
        }
        config(['laravelusers.avatar.dicebear.url' => 'https://avatars.example.test']);
        foreach (['../initials', 'identicon?seed=other', '<svg>'] as $style) {
            config(['laravelusers.avatar.dicebear.style' => $style]);
            $this->assertNull($avatar->forUser($user, 'dicebear')['src']);
        }
        config(['laravelusers.avatar.dicebear.driver' => 'unknown', 'laravelusers.avatar.ui_avatars.driver' => 'unknown']);
        $this->assertNull($avatar->forUser($user, 'dicebear')['src']);
        $this->assertNull($avatar->forUser($user, 'ui-avatars')['src']);
    }

    public function test_all_sources_can_be_saved_and_rendered_in_every_framework(): void
    {
        (require dirname(__DIR__, 2).'/src/database/avatar/2026_10_08_095110_create_laravelusers_avatar_preferences_table.php')->up();
        config(['laravelusers.avatar.per_user' => true, 'laravelusers.avatar.enabled' => true]);
        $this->actingAs($this->user());
        $user = $this->user();
        foreach (Avatar::SOURCES as $source) {
            $this->put('/users/'.$user->id, ['name' => $user->name, 'email' => $user->email, 'avatar_source' => $source])->assertSessionHasNoErrors();
            $this->assertSame($source, AvatarPreferences::formData($user)['avatarSource']);
            foreach (['bootstrap4', 'bootstrap5', 'tailwind'] as $framework) {
                config(['laravelusers.frontend' => $framework]);
                $response = $this->get('/users/'.$user->id.'/edit')->assertOk();
                $this->assertMatchesRegularExpression('/value="'.preg_quote($source, '/').'"\s+selected/', $response->getContent());
                $this->get('/users/'.$user->id)->assertOk();
            }
        }
    }

    public function test_local_dicebear_uses_the_real_installed_library_without_network_requests(): void
    {
        if (!LocalAvatars::diceBearInstalled()) {
            $user = $this->user();
            $this->assertNull((new Avatar())->forUser($user, 'dicebear')['src']);

            return;
        }
        Http::fake();
        $user = $this->user();
        $avatar = new Avatar();
        $url = $avatar->forUser($user, 'dicebear')['src'];
        $this->assertStringStartsWith('data:image/svg+xml;', $url);
        $this->assertSame($url, $avatar->forUser($user, 'dicebear')['src']);
        $this->assertNotSame($url, $avatar->forUser($this->user(), 'dicebear')['src']);
        config(['laravelusers.avatar.dicebear.style' => 'not-an-installed-style']);
        $this->assertNull($avatar->forUser($user, 'dicebear')['src']);
        Http::assertNothingSent();
    }
}
