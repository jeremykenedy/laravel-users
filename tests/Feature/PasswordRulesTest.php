<?php

namespace jeremykenedy\laravelusers\Test\Feature;

use Illuminate\Support\Facades\Hash;
use jeremykenedy\laravelusers\Test\TestCase;

class PasswordRulesTest extends TestCase
{
    public function test_default_password_limits_and_blank_password_updates_remain_compatible(): void
    {
        $user = $this->user();
        $this->actingAs($user);
        foreach (['short', str_repeat('a', 21)] as $password) {
            $this->put('/users/'.$user->id, ['name' => $user->name, 'email' => $user->email, 'password' => $password, 'password_confirmation' => $password])->assertSessionHasErrors('password');
        }
        $this->put('/users/'.$user->id, ['name' => $user->name, 'email' => $user->email, 'password' => 'simple', 'password_confirmation' => 'simple'])->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check('simple', $user->fresh()->password));
        $this->put('/users/'.$user->id, ['name' => $user->name, 'email' => $user->email, 'password' => '', 'password_confirmation' => ''])->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check('simple', $user->fresh()->password));
    }

    public function test_configured_requirements_are_enforced_by_the_server(): void
    {
        $user = $this->user();
        config(['laravelusers.password.min' => 12, 'laravelusers.password.max' => 64, 'laravelusers.password.mixed_case' => true, 'laravelusers.password.numbers' => true, 'laravelusers.password.symbols' => true]);
        $this->actingAs($user);
        foreach (['Short1!', 'lowercase123!', 'LettersOnly!', 'NoSymbols123', str_repeat('X', 65).'a1!'] as $password) {
            $this->put('/users/'.$user->id, ['name' => $user->name, 'email' => $user->email, 'password' => $password, 'password_confirmation' => $password])->assertSessionHasErrors('password');
        }
        $password = 'LongerPassword123!';
        $this->put('/users/'.$user->id, ['name' => $user->name, 'email' => $user->email, 'password' => $password, 'password_confirmation' => $password])->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check($password, $user->fresh()->password));
    }

    public function test_create_retains_long_passwords_by_default_and_applies_configured_rules(): void
    {
        $this->actingAs($this->user());
        $data = ['name' => 'CreatePassword', 'email' => 'create-password@example.com', 'password' => str_repeat('a', 40), 'password_confirmation' => str_repeat('a', 40)];
        $this->post('/users', $data)->assertSessionHasNoErrors();
        config(['laravelusers.password.create_max' => 30]);
        $data['name'] = 'SecondPassword';
        $data['email'] = 'second-password@example.com';
        $this->post('/users', $data)->assertSessionHasErrors('password');
        config(['laravelusers.password.create_max' => 64, 'laravelusers.password.min' => 12, 'laravelusers.password.mixed_case' => true, 'laravelusers.password.numbers' => true, 'laravelusers.password.symbols' => true]);
        $this->post('/users', $data)->assertSessionHasErrors('password');
        $data['password'] = $data['password_confirmation'] = 'LongerPassword123!';
        $this->post('/users', $data)->assertSessionHasNoErrors();
        $data['name'] = 'ThirdPassword';
        $data['email'] = 'third-password@example.com';
        $data['password_confirmation'] = 'DifferentPassword123!';
        $this->post('/users', $data)->assertSessionHasErrors('password_confirmation');
    }

    public function test_the_meter_can_be_disabled_without_disabling_password_validation(): void
    {
        $user = $this->user();
        $this->actingAs($user);
        foreach (['bootstrap4', 'bootstrap5', 'tailwind'] as $framework) {
            config(['laravelusers.frontend' => $framework, 'laravelusers.password.meter' => true, 'laravelusers.password.confirmation_feedback' => true]);
            $this->get('/users/'.$user->id.'/edit')->assertOk()->assertSee('data-lu-password-meter', false);
            $this->get('/users/create')->assertOk()->assertSee('data-lu-password-meter', false)->assertSee('lu-password-confirmation-error', false);
            config(['laravelusers.password.meter' => false]);
            $this->get('/users/'.$user->id.'/edit')->assertOk()->assertDontSee('data-lu-password-meter', false);
            $this->get('/users/create')->assertOk()->assertDontSee('data-lu-password-meter', false);
        }
        $this->put('/users/'.$user->id, ['name' => $user->name, 'email' => $user->email, 'password' => 'bad', 'password_confirmation' => 'bad'])->assertSessionHasErrors('password');
    }
}
