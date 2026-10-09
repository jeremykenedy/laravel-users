<?php

namespace jeremykenedy\laravelusers\Test\Feature;

use Illuminate\Http\Request;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use jeremykenedy\laravelusers\Support\NativeFormData;
use jeremykenedy\laravelusers\Test\TestCase;

class NativeFormDataTest extends TestCase
{
    public function test_nested_fields_preserve_submission_names_and_multiple_values(): void
    {
        $builder = new NativeFormData();
        $field = $builder->field('access.edit_users.roles', 'Roles', 'select', ['1', '2'], ['multiple' => true]);
        $form = $builder->form('settings', 'Settings', '/users/settings', 'PUT', [$field], Request::create('/users/settings'));

        $this->assertSame('access[edit_users][roles][]', $field['name']);
        $this->assertSame(['1', '2'], $form['values']['access']['edit_users']['roles']);
        $this->assertSame([], $form['errors']);
    }

    public function test_old_input_restores_display_values_without_exposing_passwords(): void
    {
        $builder = new NativeFormData();
        $session = $this->app['session']->driver();
        $session->flashInput(['name' => 'Retried Name', 'enabled' => 'on', 'password' => 'private-old-password']);
        $request = Request::create('/users/create');
        $request->setLaravelSession($session);
        $fields = [$builder->field('name', 'Name', 'text', 'Original'), $builder->field('enabled', 'Enabled', 'checkbox', false), $builder->field('password', 'Password', 'password', 'private-default-password')];
        $form = $builder->form('user', 'Create user', '/users', 'POST', $fields, $request);

        $this->assertSame('Retried Name', $form['values']['name']);
        $this->assertTrue($form['values']['enabled']);
        $this->assertSame('', $form['values']['password']);
        $this->assertStringNotContainsString('private-old-password', json_encode($form));
        $this->assertStringNotContainsString('private-default-password', json_encode($form));
    }

    public function test_form_errors_use_the_default_validation_bag(): void
    {
        $builder = new NativeFormData();
        $session = $this->app['session']->driver();
        $errors = new ViewErrorBag();
        $errors->put('default', new MessageBag(['email' => ['Email has already been taken.']]));
        $errors->put('other', new MessageBag(['name' => ['Another form failed.']]));
        $session->put('errors', $errors);
        $request = Request::create('/users/create');
        $request->setLaravelSession($session);
        $form = $builder->form('user', 'Create user', '/users', 'POST', [], $request);

        $this->assertSame(['email' => ['Email has already been taken.']], $form['errors']);
    }
}
