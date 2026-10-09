<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;
use jeremykenedy\laravelusers\Rules\PlainTextName;
use jeremykenedy\laravelusers\Support\AccountPreferences;
use jeremykenedy\laravelusers\Support\AppearancePreferences;
use jeremykenedy\laravelusers\Support\AvatarPreferences;
use jeremykenedy\laravelusers\Support\DeletedUsers;
use jeremykenedy\laravelusers\Support\PasswordRules;
use jeremykenedy\laravelusers\Support\UserPermissions;

class UpdateUserRequest extends FormRequest
{
    private ?Model $target = null;

    public function authorize(): bool
    {
        abort_if($this->routeIs('users.deleted.update') && !config('laravelusers.settings.enabled', false), 404);

        return true;
    }

    public function target(?int $id = null): Model
    {
        if ($this->target === null) {
            $model = config('laravelusers.defaultUserModel');
            $query = $this->routeIs('users.deleted.update') ? (new DeletedUsers())->query() : $model::query();
            $this->target = $query->findOrFail($id ?? $this->route('id') ?? $this->route('user'));
        }

        return $this->target;
    }

    public function rules(): array
    {
        $user = $this->target();
        $rules = ['name' => ['required', 'string', 'max:255', new PlainTextName()]];
        if ($this->input('email') !== '' && $this->input('email') !== $user->email) {
            $table = ($user->getConnectionName() ? $user->getConnectionName().'.' : '').$user->getTable();
            $rules['email'] = ['required', 'email', 'max:255', Rule::unique($table)];
        }
        if ($this->filled('password')) {
            $rules['password'] = PasswordRules::validation();
            $rules['password_confirmation'] = ['required', 'string', 'same:password'];
        }
        if (config('laravelusers.rolesEnabled', false)) {
            $rules['role'] = ['required', function ($attribute, $value, $fail): void {
                foreach ((array) $value as $id) {
                    if (!is_int($id) && !is_string($id)) {
                        $fail(trans('laravelusers::ui.invalid_role'));
                    }
                }
            }];
        }

        return array_merge($rules, UserPermissions::rules($user), AvatarPreferences::rules($user), AppearancePreferences::rules($user), AccountPreferences::rules($user));
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(redirect()->back()->withErrors($validator)->withInput($this->except(['password', 'password_confirmation'])));
    }
}
