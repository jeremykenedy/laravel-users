<?php

namespace jeremykenedy\laravelusers\Test\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use jeremykenedy\laravelusers\Actions\CleanupDeletedUsers;
use jeremykenedy\laravelusers\Models\UserSetting;
use jeremykenedy\laravelusers\Test\Fixtures\SoftUser;
use jeremykenedy\laravelusers\Test\TestCase;

class CleanupTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-08 12:00:00');
        Schema::table('users', fn (Blueprint $table) => $table->softDeletes());
        config(['laravelusers.defaultUserModel' => SoftUser::class, 'laravelusers.softDeletedEnabled' => true, 'laravelusers.settings.enabled' => true]);
        (require dirname(__DIR__, 2).'/src/database/settings/2026_10_08_145311_create_laravelusers_settings_table.php')->up();
        Gate::define('manage-laravelusers-settings', fn ($user) => $user->id === 1);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function deletedAt($date): SoftUser
    {
        $name = bin2hex(random_bytes(8));
        $user = SoftUser::findOrFail($this->user(['name' => $name, 'email' => $name.'@example.com'])->id);
        $user->delete();
        $user->deleted_at = $date;
        $user->save();

        return $user;
    }

    public function test_cleanup_is_disabled_by_default_and_never_deletes_active_users(): void
    {
        $active = $this->user();
        $deleted = $this->deletedAt(now()->subYear());
        $this->artisan('laravelusers:prune-deleted')->expectsOutput('Permanently deleted 0 users.')->assertExitCode(0);
        $this->assertNotNull($deleted->fresh());
        config(['laravelusers.cleanup.enabled' => true]);
        $this->artisan('laravelusers:prune-deleted')->expectsOutput('Permanently deleted 1 users.')->assertExitCode(0);
        $this->assertNull(SoftUser::withTrashed()->find($deleted->id));
        $this->assertNotNull($active->fresh());
    }

    public function test_retention_boundaries_and_all_supported_units(): void
    {
        foreach (['minutes', 'hours', 'days', 'months', 'years'] as $unit) {
            $cutoff = match ($unit) {
                'minutes' => now()->subMinutes(2), 'hours' => now()->subHours(2), 'days' => now()->subDays(2), 'months' => now()->subMonthsNoOverflow(2), 'years' => now()->subYearsNoOverflow(2),
            };
            config(['laravelusers.cleanup.enabled' => true, 'laravelusers.cleanup.amount' => 2, 'laravelusers.cleanup.unit' => $unit]);
            $eligible = $this->deletedAt($cutoff);
            $recent = $this->deletedAt($cutoff->copy()->addSecond());
            $this->assertSame(1, $this->app->make(CleanupDeletedUsers::class)->handle());
            $this->assertNull(SoftUser::withTrashed()->find($eligible->id));
            $this->assertNotNull($recent->fresh());
            $recent->restore();
        }
        $user = $this->deletedAt(now());
        config(['laravelusers.cleanup.unit' => 'immediately', 'laravelusers.cleanup.amount' => 0]);
        $this->assertSame(1, $this->app->make(CleanupDeletedUsers::class)->handle());
        $this->assertNull(SoftUser::withTrashed()->find($user->id));
    }

    public function test_enabling_requires_confirmation_and_saves_only_the_cleanup_section(): void
    {
        $this->actingAs($this->user());
        UserSetting::create(['key' => 'global', 'value' => ['profileCardColor' => '#123456']]);
        $data = ['enabled' => 1, 'amount' => 180, 'unit' => 'days'];
        $this->putJson('/users/settings/cleanup', $data)->assertUnprocessable()->assertJsonValidationErrors('confirmation');
        $this->assertNull(UserSetting::find('cleanup'));
        $this->put('/users/settings/cleanup', $data + ['confirmation' => 'permanently delete'])->assertRedirect();
        $this->assertSame(['enabled' => true, 'amount' => 180, 'unit' => 'days'], UserSetting::findOrFail('cleanup')->value);
        $this->assertSame('#123456', UserSetting::findOrFail('global')->value['profileCardColor']);
        $this->put('/users/settings/cleanup', ['enabled' => 0, 'amount' => 180, 'unit' => 'days'])->assertRedirect();
        $this->assertFalse(UserSetting::findOrFail('cleanup')->value['enabled']);
    }

    public function test_cleanup_settings_respect_permissions_and_reject_invalid_durations(): void
    {
        $this->actingAs($this->user());
        $data = ['enabled' => 1, 'amount' => 180, 'unit' => 'days', 'confirmation' => 'permanently delete'];
        config(['laravelusers.access.edit_cleanup.mode' => 'deny']);
        $this->putJson('/users/settings/cleanup', $data)->assertForbidden();
        $this->get('/users/settings')->assertOk()->assertDontSee('id="lu-cleanup-form"', false);
        config(['laravelusers.access.edit_cleanup.mode' => 'inherit']);
        $this->get('/users/settings')->assertOk()->assertSee('class="lu-cleanup-warning" role="alert"', false)->assertSee('data-lu-icon="warning"', false);
        foreach ([['amount' => 0], ['amount' => -1], ['amount' => 10001], ['unit' => 'seconds'], ['confirmation' => 'yes']] as $changes) {
            $this->putJson('/users/settings/cleanup', array_replace($data, $changes))->assertUnprocessable();
        }
        config(['laravelusers.access.force_delete.mode' => 'deny']);
        $this->putJson('/users/settings/cleanup', $data)->assertForbidden();
    }
}
