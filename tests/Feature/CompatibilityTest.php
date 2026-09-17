<?php

namespace jeremykenedy\laravelusers\Test\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use jeremykenedy\laravelusers\LaravelUsersServiceProvider;
use jeremykenedy\laravelusers\Test\Fixtures\Account;
use jeremykenedy\laravelusers\Test\TestCase;

class CompatibilityTest extends TestCase
{
    public function test_custom_user_table_and_connection_are_used_for_validation_and_crud(): void
    {
        config(['database.connections.accounts' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''], 'laravelusers.defaultUserModel' => Account::class]);
        Schema::connection('accounts')->create('accounts', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('email')->unique();
            $table->string('password');
            $table->timestamps();
        });
        $this->actingAs($this->user(['name' => 'existing', 'email' => 'existing@example.com']));
        $data = ['name' => 'existing', 'email' => 'existing@example.com', 'password' => 'password', 'password_confirmation' => 'password'];
        $this->post('/users', $data)->assertSessionHas('success');
        $this->assertSame(1, Account::count());
        $this->post('/users', $data)->assertSessionHasErrors(['name', 'email']);
        $this->put('/users/1', ['name' => 'account', 'email' => 'account@example.com'])->assertSessionHas('success');
        $this->get('/users/1')->assertOk()->assertSee('account@example.com');
        $this->postJson('/search-users', ['user_search_box' => 'account'])->assertJsonCount(1)->assertJsonPath('0.name', 'account');
    }

    public function test_validation_never_flashes_passwords_to_session(): void
    {
        $this->actingAs($this->user());
        foreach (['post', 'put'] as $method) {
            $this->$method($method === 'post' ? '/users' : '/users/1', ['name' => '', 'password' => 'private-value', 'password_confirmation' => 'another-private-value'])->assertSessionHasErrors();
            $this->assertArrayNotHasKey('password', session()->getOldInput());
            $this->assertArrayNotHasKey('password_confirmation', session()->getOldInput());
        }
    }

    public function test_default_configuration_and_publish_destinations_remain_available(): void
    {
        $this->assertSame('bootstrap4', config('laravelusers.frontend'));
        $this->assertSame('light', config('laravelusers.theme'));
        $this->assertFalse(config('laravelusers.themeToggle'));
        $this->assertTrue(config('laravelusers.authEnabled'));
        $this->assertFalse(config('laravelusers.rolesEnabled'));
        $this->assertSame('laravelusers::layouts.app', config('laravelusers.laravelUsersBladeExtended'));
        $paths = ServiceProvider::pathsToPublish(LaravelUsersServiceProvider::class, 'laravelusers');
        $this->assertContains(config_path('laravelusers.php'), $paths);
        $this->assertContains(resource_path('views/vendor/laravelusers'), $paths);
        $this->assertCount(3, $paths);
    }
}
