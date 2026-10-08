<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;
use jeremykenedy\laravelusers\Rules\PlainTextName;

class CreateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userModel = config('laravelusers.defaultUserModel');
        $model = new $userModel();
        $table = ($model->getConnectionName() ? $model->getConnectionName().'.' : '').$model->getTable();
        $reset = $this->boolean('force_password_reset') && $this->boolean('send_welcome_email');
        $rules = [
            'name'                  => ['required', 'string', 'max:255', Rule::unique($table), 'alpha_dash', new PlainTextName()],
            'email'                 => ['required', 'email', 'max:255', Rule::unique($table)],
            'password'              => [$reset ? 'nullable' : 'required', 'string', 'confirmed', 'min:6'],
            'password_confirmation' => [$reset ? 'nullable' : 'required', 'string', 'same:password'],
        ];
        if (config('laravelusers.rolesEnabled', false)) {
            $rules['role'] = ['required'];
        }

        return array_merge($rules, $this->welcomeRules());
    }

    private function welcomeRules(): array
    {
        $rules = [
            'send_welcome_email'   => [$this->boolean('force_password_reset') ? 'required' : 'sometimes', 'boolean', Rule::in(config('laravelusers.welcome.enabled', true) ? [0, 1] : [0])],
            'force_password_reset' => ['sometimes', 'boolean', Rule::in(config('laravelusers.welcome.force_password_reset', true) ? [0, 1] : [0])],
        ];
        if ($this->boolean('force_password_reset')) {
            $rules['send_welcome_email'][] = Rule::in([1]);
        }

        return $rules;
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(redirect()->back()->withErrors($validator)->withInput($this->except(['password', 'password_confirmation'])));
    }

    public function messages(): array
    {
        return [
            'name.unique'           => trans('laravelusers::laravelusers.messages.userNameTaken'),
            'name.required'         => trans('laravelusers::laravelusers.messages.userNameRequired'),
            'name'                  => trans('laravelusers::laravelusers.messages.userNameInvalid'),
            'email.required'        => trans('laravelusers::laravelusers.messages.emailRequired'),
            'email.email'           => trans('laravelusers::laravelusers.messages.emailInvalid'),
            'password.required'     => trans('laravelusers::laravelusers.messages.passwordRequired'),
            'password.min'          => trans('laravelusers::laravelusers.messages.PasswordMin'),
            'role.required'         => trans('laravelusers::laravelusers.messages.roleRequired'),
            'send_welcome_email.in' => trans('laravelusers::ui.welcome_required'),
        ];
    }
}
