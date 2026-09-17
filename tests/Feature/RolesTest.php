<?php

namespace jeremykenedy\laravelusers\Test\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use jeremykenedy\laravelusers\Test\Fixtures\Role;
use jeremykenedy\laravelusers\Test\Fixtures\RoleUser;
use jeremykenedy\laravelusers\Test\TestCase;

class RolesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        RoleUser::$failAssignment = false;
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });
        Schema::create('role_user', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id');
            $table->unsignedBigInteger('user_id');
        });
        config(['laravelusers.rolesEnabled' => true, 'laravelusers.rolesMiddlwareEnabled' => false, 'laravelusers.roleModel' => Role::class, 'laravelusers.defaultUserModel' => RoleUser::class]);
        $this->actingAs($this->user());
    }

    public function test_roles_are_required_attached_replaced_and_returned_in_search(): void
    {
        $admin = Role::create(['name' => 'Admin']);
        $member = Role::create(['name' => 'User']);
        $data = ['name' => 'roleuser', 'email' => 'role@example.com', 'password' => 'password', 'password_confirmation' => 'password'];
        $this->post('/users', $data)->assertSessionHasErrors('role');
        $this->post('/users', array_merge($data, ['role' => $admin->id]))->assertRedirect('/users');
        $user = RoleUser::where('name', 'roleuser')->firstOrFail();
        $this->assertSame([$admin->id], $user->roles->modelKeys());
        $this->put('/users/'.$user->id, ['name' => $user->name, 'email' => $user->email, 'role' => $member->id])->assertSessionHas('success');
        $this->assertSame([$member->id], $user->fresh()->roles->modelKeys());
        $this->postJson('/search-users', ['user_search_box' => 'roleuser'])->assertOk()->assertJsonPath('0.roles.0.name', 'User');
        foreach (['bootstrap4', 'bootstrap5', 'tailwind'] as $framework) {
            config(['laravelusers.frontend' => $framework]);
            $this->get('/users')->assertOk()->assertSee('roleuser');
            $this->get('/users/create')->assertOk()->assertSee('Admin');
            $this->get('/users/'.$user->id.'/edit')->assertOk()->assertSee('selected');
            $this->get('/users/'.$user->id)->assertOk();
        }
    }

    public function test_role_middleware_protects_reads_writes_and_search(): void
    {
        $this->app['router']->aliasMiddleware('deny-users', DenyUsers::class);
        config(['laravelusers.rolesMiddlwareEnabled' => true, 'laravelusers.rolesMiddlware' => 'deny-users']);
        $this->get('/users')->assertForbidden();
        $this->post('/users')->assertForbidden();
        $this->put('/users/1')->assertForbidden();
        $this->delete('/users/1')->assertForbidden();
        $this->postJson('/search-users')->assertForbidden();
    }

    public function test_failed_role_assignment_rolls_back_user_creation(): void
    {
        RoleUser::$failAssignment = true;
        $this->withoutExceptionHandling();

        try {
            $this->post('/users', ['name' => 'failed', 'email' => 'failed@example.com', 'password' => 'password', 'password_confirmation' => 'password', 'role' => 1]);
            $this->fail('Expected role assignment failure.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Role assignment failed', $exception->getMessage());
        }
        $this->assertDatabaseMissing('users', ['name' => 'failed']);
    }

    public function test_failed_role_replacement_keeps_user_and_previous_roles(): void
    {
        $role = Role::create(['name' => 'Admin']);
        $user = RoleUser::findOrFail(1);
        $user->attachRole($role->id);
        RoleUser::$failAssignment = true;
        $this->withoutExceptionHandling();

        try {
            $this->put('/users/'.$user->id, ['name' => 'changed', 'email' => $user->email, 'role' => 2]);
            $this->fail('Expected role assignment failure.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Role assignment failed', $exception->getMessage());
        }
        $this->assertSame($user->name, $user->fresh()->name);
        $this->assertSame([$role->id], $user->fresh()->roles->modelKeys());
    }
}

class DenyUsers
{
    public function handle($request, $next)
    {
        abort(403);
    }
}
