<?php

namespace jeremykenedy\laravelusers\Test\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use jeremykenedy\laravelusers\Support\Avatar;
use jeremykenedy\laravelusers\Support\AvatarPreferences;
use jeremykenedy\laravelusers\Test\Fixtures\SoftUser;
use jeremykenedy\laravelusers\Test\Fixtures\User;
use jeremykenedy\laravelusers\Test\TestCase;

class AvatarPreferencesTest extends TestCase
{
    private function enable(): void
    {
        (require dirname(__DIR__, 2).'/src/database/avatar/2026_10_08_095110_create_laravelusers_avatar_preferences_table.php')->up();
        config(['laravelusers.avatar.per_user' => true, 'laravelusers.avatar.enabled' => true]);
    }

    public function test_defaults_do_not_query_or_change_a_host_schema_and_ignore_unrequested_fields(): void
    {
        $user = $this->user();
        $this->actingAs($user);
        $columns = Schema::getColumnListing('users');
        $this->get('/users/create')->assertOk()->assertDontSee('name="avatar_source"', false);
        $this->put('/users/'.$user->id, ['name' => $user->name, 'email' => $user->email, 'avatar_source' => 'gravatar'])->assertSessionHasNoErrors();
        $this->assertFalse(Schema::hasTable('laravelusers_avatar_preferences'));
        $this->assertSame($columns, Schema::getColumnListing('users'));
        $this->assertNull((new Avatar())->forUser($user)['src']);
    }

    public function test_missing_optional_migration_keeps_existing_pages_and_updates_working(): void
    {
        config(['laravelusers.avatar.per_user' => true]);
        $user = $this->user();
        $this->actingAs($user);
        $this->get('/users')->assertOk();
        $this->get('/users/'.$user->id.'/edit')->assertOk()->assertSee('optional avatar preferences migration');
        $this->put('/users/'.$user->id, ['name' => $user->name, 'email' => $user->email])->assertSessionHasNoErrors();
        $this->put('/users/'.$user->id, ['name' => 'Changed', 'email' => $user->email, 'avatar_source' => 'initials'])->assertSessionHasErrors('avatar_source');
        $this->assertSame($user->name, $user->fresh()->name);
    }

    public function test_existing_users_inherit_global_settings_and_explicit_choices_survive_global_changes(): void
    {
        $this->enable();
        $user = $this->user();
        $avatar = new Avatar();
        config(['laravelusers.avatar.source' => 'gravatar']);
        $this->assertStringContainsString('gravatar.com', $avatar->forUser($user)['src']);
        $this->assertDatabaseCount('laravelusers_avatar_preferences', 0);
        AvatarPreferences::save($user, ['avatar_source' => 'initials']);
        $this->assertNull($avatar->forUser($user->fresh())['src']);
        $this->assertSame('initials', $avatar->forUser($user)['fallback']);
        config(['laravelusers.avatar.source' => 'avatar']);
        AvatarPreferences::save($user, []);
        $this->assertNull($avatar->forUser($user)['src']);
        AvatarPreferences::save($user, ['avatar_source' => 'inherit']);
        $this->assertDatabaseCount('laravelusers_avatar_preferences', 0);
        config(['laravelusers.avatar.source' => 'gravatar']);
        $this->assertStringContainsString('gravatar.com', $avatar->forUser($user)['src']);
    }

    public function test_create_edit_search_and_all_framework_forms_use_the_saved_source(): void
    {
        $this->enable();
        $actor = $this->user();
        $this->actingAs($actor);
        $data = ['name' => 'AvatarUser', 'email' => 'avatar@example.com', 'password' => 'password', 'password_confirmation' => 'password', 'avatar_source' => 'gravatar'];
        $this->post('/users', $data)->assertSessionHasNoErrors();
        $user = User::where('name', 'AvatarUser')->firstOrFail();
        foreach (['bootstrap4', 'bootstrap5', 'tailwind'] as $framework) {
            config(['laravelusers.frontend' => $framework]);
            $this->get('/users/create')->assertOk()->assertSee('name="avatar_source"', false)->assertSee('Use package setting');
            $response = $this->get('/users/'.$user->id.'/edit')->assertOk();
            $this->assertMatchesRegularExpression('/value="gravatar"\s+selected/', $response->getContent());
            $this->get('/users/'.$user->id)->assertOk()->assertSee('gravatar.com');
        }
        $this->postJson('/search-users', ['user_search_box' => 'AvatarUser', 'include_avatar' => 1])->assertJsonPath('avatars.'.$user->id.'.src', (new Avatar())->forUser($user)['src']);
        $this->put('/users/'.$user->id, ['name' => $user->name, 'email' => $user->email, 'avatar_source' => 'initials'])->assertSessionHasNoErrors();
        $this->assertNull((new Avatar())->forUser($user->fresh())['src']);
        $this->put('/users/'.$user->id, ['name' => $user->name, 'email' => $user->email])->assertSessionHasNoErrors();
        $this->assertSame('initials', AvatarPreferences::formData($user)['avatarSource']);
        foreach (['javascript:alert(1)', ['gravatar'], 'unknown'] as $invalid) {
            $this->put('/users/'.$user->id, ['name' => 'Changed', 'email' => $user->email, 'avatar_source' => $invalid])->assertSessionHasErrors('avatar_source');
        }
        $this->assertSame('AvatarUser', $user->fresh()->name);
    }

    public function test_avatar_attribute_safety_remains_in_effect_for_a_per_user_choice(): void
    {
        $this->enable();
        $user = $this->user();
        AvatarPreferences::save($user, ['avatar_source' => 'avatar']);
        foreach (['javascript:alert(1)', 'data:image/svg+xml,<svg/>', '//evil.example/photo'] as $url) {
            $user->setAttribute('avatar', $url);
            $this->assertNull((new Avatar())->forUser($user)['src']);
        }
        $user->setAttribute('avatar', '/storage/profile.jpg');
        $this->assertSame('/storage/profile.jpg', (new Avatar())->forUser($user)['src']);
    }

    public function test_listing_fetches_preferences_together_and_handles_iterators(): void
    {
        $this->enable();
        $users = collect(range(1, 5))->map(fn () => $this->user());
        AvatarPreferences::save($users[0], ['avatar_source' => 'gravatar']);
        $connection = $users[0]->getConnection();
        $connection->enableQueryLog();
        $connection->flushQueryLog();
        $avatars = (new Avatar())->listing((function () use ($users) { yield from $users; })());
        $queries = array_filter($connection->getQueryLog(), fn ($query) => str_contains($query['query'], 'select * from "laravelusers_avatar_preferences"'));
        $this->assertCount(1, $queries);
        $this->assertCount(5, $avatars);
        $this->assertStringContainsString('gravatar.com', $avatars[$users[0]->id]['src']);
        $this->assertNull($avatars[$users[1]->id]['src']);
    }

    public function test_soft_deletion_and_restore_keep_preferences_and_permanent_deletion_removes_them(): void
    {
        $this->enable();
        Schema::table('users', fn (Blueprint $table) => $table->softDeletes());
        config(['laravelusers.defaultUserModel' => SoftUser::class]);
        $user = SoftUser::findOrFail($this->user()->id);
        AvatarPreferences::save($user, ['avatar_source' => 'gravatar']);
        $this->assertNull((new Avatar())->forUser(User::findOrFail($user->id))['src']);
        $user->delete();
        $this->assertDatabaseCount('laravelusers_avatar_preferences', 1);
        $user->restore();
        $this->assertStringContainsString('gravatar.com', (new Avatar())->forUser($user)['src']);
        $user->forceDelete();
        $this->assertDatabaseCount('laravelusers_avatar_preferences', 0);
    }

    public function test_preferences_use_the_users_connection_and_roll_back_with_user_changes(): void
    {
        config(['database.connections.avatar_host' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''], 'laravelusers.avatar.per_user' => true]);
        $user = $this->user()->setConnection('avatar_host');
        $schema = $user->getConnection()->getSchemaBuilder();
        $schema->create('laravelusers_avatar_preferences', function (Blueprint $table) {
            $table->string('user_key', 64)->primary();
            $table->string('source', 16);
        });
        $user->getConnection()->beginTransaction();
        AvatarPreferences::save($user, ['avatar_source' => 'gravatar']);
        $this->assertStringContainsString('gravatar.com', (new Avatar())->forUser($user)['src']);
        $user->getConnection()->rollBack();
        $this->assertNull((new Avatar())->forUser($user)['src']);
        $this->assertFalse(Schema::hasTable('laravelusers_avatar_preferences'));
    }
}
