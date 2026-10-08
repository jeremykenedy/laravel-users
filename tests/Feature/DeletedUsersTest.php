<?php

namespace jeremykenedy\laravelusers\Test\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use jeremykenedy\laravelusers\Support\UserActivity;
use jeremykenedy\laravelusers\Test\Fixtures\SoftUser;
use jeremykenedy\laravelusers\Test\TestCase;

class DeletedUsersTest extends TestCase
{
    private function enable(): SoftUser
    {
        Schema::table('users', fn (Blueprint $table) => $table->softDeletes());
        config(['laravelusers.softDeletedEnabled' => true, 'laravelusers.defaultUserModel' => SoftUser::class, 'laravelusers.bulkActions' => true]);
        $user = $this->user();
        $admin = SoftUser::findOrFail($user->id);
        $this->actingAs($admin);

        return $admin;
    }

    public function test_deleted_routes_require_opt_in_and_a_soft_delete_model(): void
    {
        $this->actingAs($this->user());
        $this->get('/users/deleted')->assertNotFound();
        $this->post('/users/1/restore')->assertNotFound();
        $this->delete('/users/1/force')->assertNotFound();
        config(['laravelusers.softDeletedEnabled' => true]);
        $this->get('/users/deleted')->assertNotFound();
    }

    public function test_deleted_users_have_their_own_list_and_can_be_restored_or_removed(): void
    {
        $this->enable();
        $user = $this->user(['name' => 'DeletedAccount']);
        $this->delete('/users/'.$user->id)->assertRedirect('/users');
        foreach (['bootstrap4', 'bootstrap5', 'tailwind'] as $framework) {
            config(['laravelusers.frontend' => $framework]);
            $this->get('/users')->assertOk()->assertDontSee('DeletedAccount');
            $this->get('/users/deleted')->assertOk()->assertSee('DeletedAccount')->assertSee('Restore')->assertSee('Permanently delete');
        }
        $this->get('/users/'.$user->id)->assertNotFound();
        $this->post('/users/'.$user->id.'/restore')->assertRedirect('/users/deleted');
        $this->assertFalse(SoftUser::findOrFail($user->id)->trashed());
        $this->delete('/users/'.$user->id);
        $this->delete('/users/'.$user->id.'/force')->assertRedirect('/users/deleted');
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->post('/users/999/restore')->assertNotFound();
    }

    public function test_bulk_delete_restore_and_permanent_delete_are_atomic_and_protect_self(): void
    {
        $admin = $this->enable();
        $one = $this->user();
        $two = $this->user();
        $this->post('/users/bulk', ['action' => 'delete', 'ids' => [$one->id, $admin->id]])->assertSessionHasErrors('ids');
        $this->assertNull(SoftUser::findOrFail($one->id)->deleted_at);
        $this->post('/users/bulk', ['action' => 'delete', 'ids' => [$one->id, 999]])->assertSessionHasErrors('ids');
        $this->assertNull(SoftUser::findOrFail($one->id)->deleted_at);
        $ids = [$one->id, $two->id];
        $this->post('/users/bulk', ['action' => 'delete', 'ids' => $ids])->assertRedirect('/users');
        $this->assertCount(2, SoftUser::onlyTrashed()->get());
        $this->post('/users/bulk', ['action' => 'restore', 'ids' => $ids])->assertRedirect('/users/deleted');
        $this->assertCount(0, SoftUser::onlyTrashed()->get());
        $this->post('/users/bulk', ['action' => 'delete', 'ids' => $ids]);
        $this->post('/users/bulk', ['action' => 'force_delete', 'ids' => $ids])->assertRedirect('/users/deleted');
        $this->assertDatabaseMissing('users', ['id' => $one->id]);
        $this->assertDatabaseMissing('users', ['id' => $two->id]);
    }

    public function test_bulk_actions_reject_disabled_invalid_and_oversized_requests(): void
    {
        $this->actingAs($this->user());
        $this->post('/users/bulk', ['action' => 'delete', 'ids' => [2]])->assertForbidden();
        config(['laravelusers.bulkActions' => true, 'laravelusers.bulkLimit' => 1]);
        $this->post('/users/bulk', ['action' => 'unknown', 'ids' => [1, 2]])->assertSessionHasErrors(['action', 'ids']);
        $this->post('/users/bulk', ['action' => 'delete', 'ids' => [1, 1]])->assertSessionHasErrors();
        $this->post('/users/bulk', ['action' => 'delete', 'ids' => [['id' => 1]]])->assertSessionHasErrors();
        $this->post('/users/bulk', ['action' => 'delete', 'ids' => []])->assertSessionHasErrors();
    }

    public function test_restore_preserves_login_history_and_permanent_delete_removes_it(): void
    {
        $this->enable();
        (require dirname(__DIR__, 2).'/src/database/migrations/2026_10_07_000000_create_laravelusers_login_activity_table.php')->up();
        config(['laravelusers.activity.login' => true]);
        $user = SoftUser::findOrFail($this->user()->id);
        $activity = $this->app->make(UserActivity::class);
        $activity->recordLogin($user, Request::create('/login'));
        $this->delete('/users/'.$user->id);
        $this->assertNotNull($activity->lastLogin($user));
        $this->post('/users/'.$user->id.'/restore');
        $this->assertNotNull($activity->lastLogin($user));
        $this->delete('/users/'.$user->id);
        $this->delete('/users/'.$user->id.'/force');
        $this->assertNull($activity->lastLogin($user));
    }
}
