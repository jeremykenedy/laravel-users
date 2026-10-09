<?php

namespace jeremykenedy\laravelusers\Test\Feature;

use Illuminate\Support\Facades\Hash;
use jeremykenedy\laravelusers\Test\Fixtures\User;
use jeremykenedy\laravelusers\Test\TestCase;

class UsersTest extends TestCase
{
    public function test_guests_cannot_access_any_user_endpoint(): void
    {
        foreach (['/users', '/users/create', '/users/1', '/users/1/edit'] as $url) {
            $this->get($url)->assertRedirect('/login');
        }
        $this->post('/users')->assertRedirect('/login');
        $this->put('/users/1')->assertRedirect('/login');
        $this->delete('/users/1')->assertRedirect('/login');
        $this->postJson('/search-users')->assertStatus(401);
    }

    public function test_legacy_pages_render_and_routes_keep_their_names(): void
    {
        $user = $this->user();
        $this->actingAs($user);
        $this->get('/users')->assertOk()->assertViewIs('laravelusers::usersmanagement.show-users')->assertSee('bootstrap/4.0.0');
        $this->get('/users/create')->assertOk();
        $this->get('/users/'.$user->id)->assertOk()->assertSee($user->email);
        $this->get('/users/'.$user->id.'/edit')->assertOk();
        $this->assertSame(url('/users'), route('users'));
        $this->assertSame(url('/users/'.$user->id), route('user.destroy', $user->id));
    }

    public function test_create_validates_hashes_password_and_does_not_mass_assign_extra_fields(): void
    {
        $this->actingAs($this->user());
        $this->post('/users', ['name' => 'new'])->assertSessionHasErrors(['email', 'password']);
        $this->assertSame(1, User::count());
        $this->post('/users', ['name' => 'new', 'email' => 'new@example.com', 'password' => 'secret123', 'password_confirmation' => 'secret123', 'id' => 999])->assertRedirect('/users')->assertSessionHas('success');
        $user = User::where('name', 'new')->firstOrFail();
        $this->assertTrue(Hash::check('secret123', $user->password));
        $this->assertNotEquals(999, $user->id);
        $this->post('/users', ['name' => 'new', 'email' => 'new@example.com', 'password' => 'secret123', 'password_confirmation' => 'wrong'])->assertSessionHasErrors(['name', 'email', 'password']);
    }

    public function test_update_preserves_password_when_blank_and_validates_changes(): void
    {
        $user = $this->user();
        $this->actingAs($user);
        $this->from('/users/'.$user->id.'/edit')->put('/users/'.$user->id, ['name' => 'Updated', 'email' => $user->email, 'password' => ''])->assertSessionHas('success');
        $this->assertSame('Updated', $user->fresh()->name);
        $this->assertSame($user->password, $user->fresh()->password);
        $this->put('/users/'.$user->id, ['name' => ['bad'], 'email' => 'bad', 'password' => 'short'])->assertSessionHasErrors(['name', 'email', 'password']);
        $this->put('/users/'.$user->id, ['name' => 'Updated', 'email' => $user->email, 'password' => 'newpassword', 'password_confirmation' => 'newpassword'])->assertSessionHas('success');
        $this->assertTrue(Hash::check('newpassword', $user->fresh()->password));
    }

    public function test_delete_protects_current_user_and_deletes_another_user(): void
    {
        $user = $this->user();
        $other = $this->user();
        $this->actingAs($user);
        $this->delete('/users/'.$user->id)->assertSessionHas('error');
        $this->assertNotNull($user->fresh());
        $this->delete('/users/'.$other->id)->assertRedirect('/users');
        $this->assertNull($other->fresh());
    }

    public function test_failed_updates_keep_passwords_out_of_old_input_and_leave_the_account_unchanged(): void
    {
        $user = $this->user();
        $this->actingAs($user);
        $this->from('/users/'.$user->id.'/edit')->put('/users/'.$user->id, [
            'name' => 'Changed', 'email' => 'invalid', 'password' => 'newpassword', 'password_confirmation' => 'different',
        ])->assertSessionHasErrors(['email', 'password', 'password_confirmation']);
        $this->assertSame('Changed', session()->getOldInput('name'));
        $this->assertNull(session()->getOldInput('password'));
        $this->assertNull(session()->getOldInput('password_confirmation'));
        $this->assertSame($user->name, $user->fresh()->name);
        $this->assertSame($user->email, $user->fresh()->email);
        $this->assertSame($user->password, $user->fresh()->password);
    }

    public function test_updates_cannot_write_unvalidated_model_attributes(): void
    {
        $user = $this->user();
        $this->actingAs($user)->put('/users/'.$user->id, [
            'name' => 'Changed', 'email' => $user->email, 'id' => 999, 'email_verified_at' => now()->toIso8601String(),
        ])->assertSessionHas('success')->assertSessionHasNoErrors();
        $this->assertSame('Changed', $user->fresh()->name);
        $this->assertSame($user->id, $user->fresh()->id);
        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_auth_can_be_disabled_for_crud_but_search_keeps_its_existing_auth_requirement(): void
    {
        config(['laravelusers.authEnabled' => false]);
        $user = $this->user();
        $this->get('/users')->assertOk();
        $this->delete('/users/'.$user->id)->assertRedirect('/users');
        $this->assertNull($user->fresh());
        $this->postJson('/search-users', ['user_search_box' => 'person'])->assertStatus(401);
    }

    public function test_search_returns_json_prefix_matches_without_roles_or_passwords(): void
    {
        $user = $this->user();
        $this->actingAs($user);
        $this->user(['name' => 'Alice', 'email' => 'alice@example.com']);
        $this->user(['name' => 'Sally', 'email' => 'sally@example.com']);
        $response = $this->postJson('/search-users', ['user_search_box' => 'Ali'])->assertOk()->assertJsonCount(1)->assertJsonPath('0.name', 'Alice');
        $this->assertArrayNotHasKey('password', $response->json('0'));
        $this->assertArrayNotHasKey('roles', $response->json('0'));
        $this->postJson('/search-users', ['user_search_box' => 'missing'])->assertExactJson([]);
        foreach (['', ['invalid'], str_repeat('x', 256)] as $term) {
            $this->postJson('/search-users', ['user_search_box' => $term])->assertStatus(422)->assertJsonStructure(['errors' => ['user_search_box']]);
        }
    }

    public function test_pagination_and_unpaginated_lists_remain_available(): void
    {
        $this->actingAs($this->user());
        $this->user();
        config(['laravelusers.paginateListSize' => 1]);
        $this->get('/users')->assertViewHas('users', function ($users) {
            return $users->count() === 1 && $users->total() === 2;
        });
        config(['laravelusers.enablePagination' => false]);
        $this->get('/users')->assertViewHas('users', function ($users) {
            return $users->count() === 2;
        });
    }

    public function test_missing_users_return_404(): void
    {
        $this->actingAs($this->user());
        $this->get('/users/999')->assertNotFound();
        $this->get('/users/999/edit')->assertNotFound();
        $this->put('/users/999')->assertNotFound();
        $this->delete('/users/999')->assertNotFound();
    }

    public function test_usernames_reject_markup_on_create_and_update(): void
    {
        $user = $this->user();
        $this->actingAs($user);
        $markup = '<img src=x onerror=alert(1)>';
        $this->post('/users', ['name' => $markup, 'email' => 'markup@example.com', 'password' => 'password123', 'password_confirmation' => 'password123'])->assertSessionHasErrors('name');
        $this->assertDatabaseMissing('users', ['email' => 'markup@example.com']);
        $this->put('/users/'.$user->id, ['name' => $markup, 'email' => $user->email])->assertSessionHasErrors('name');
        $this->assertSame($user->name, $user->fresh()->name);
        $this->put('/users/'.$user->id, ['name' => 'A normal name', 'email' => $user->email])->assertSessionHasNoErrors();
    }
}
