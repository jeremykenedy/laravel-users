<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\App\Http\Requests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use jeremykenedy\laravelusers\Rules\PlainTextName;
use jeremykenedy\laravelusers\Support\AccountPreferences;
use jeremykenedy\laravelusers\Support\AppearancePreferences;
use jeremykenedy\laravelusers\Support\Avatar;
use jeremykenedy\laravelusers\Support\AvatarPreferences;
use jeremykenedy\laravelusers\Support\PasswordRules;

class UpdateAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        $section = $this->input('section');

        return $this->user() instanceof Model && AccountPreferences::editable($this->user()) && is_string($section)
            && in_array($section, ['profile', 'appearance', 'email', 'password'], true)
            && ($section === 'appearance' ? (config('laravelusers.account.appearance', true) || config('laravelusers.account.avatar', true)) : (bool) config('laravelusers.account.'.$section, true));
    }

    public function rules(): array
    {
        $user = $this->user();
        $table = ($user->getConnectionName() ? $user->getConnectionName().'.' : '').$user->getTable();
        $rules = ['section' => ['required', Rule::in(['profile', 'appearance', 'email', 'password'])]];
        if ($this->input('section') === 'profile') {
            return $rules + [
                'username'  => ['required', 'string', 'max:255', new PlainTextName(), Rule::unique($table, config('laravelusers.account.username_column', 'name'))->ignore($user->getKey(), $user->getKeyName())],
                'full_name' => ['required', 'string', 'max:255', new PlainTextName()],
            ];
        }
        if ($this->input('section') === 'appearance') {
            $rules['avatar_source'] = [config('laravelusers.account.avatar', true) ? 'sometimes' : 'prohibited', Rule::in(array_merge(['inherit'], Avatar::SOURCES)), function ($attribute, $value, $fail) use ($user) {
                if (!AvatarPreferences::available($user)) {
                    $fail(trans('laravelusers::ui.avatar_migration_required'));
                }
            }];
            foreach (['', '_dark'] as $mode) {
                $allowed = config('laravelusers.account.appearance', true) ? 'sometimes' : 'prohibited';
                $available = $mode === '' ? AppearancePreferences::strengthAvailable($user) : AppearancePreferences::darkAvailable($user);
                $ready = function ($attribute, $value, $fail) use ($available) {
                    if (!$available) {
                        $fail(trans('laravelusers::ui.appearance_migration_required'));
                    }
                };
                $rules['user_card'.$mode.'_color'] = [$allowed, 'nullable', 'string', 'regex:/\A#[a-f0-9]{6}\z/i', $ready];
                $rules['user_card'.$mode.'_gradient'] = [$allowed, Rule::in(['inherit', 'on', 'off']), $ready];
                $rules['user_card'.$mode.'_gradient_strength'] = [$allowed, 'nullable', 'integer', 'min:0', 'max:100', $ready];
            }

            return $rules;
        }
        $rules['current_password'] = ['required', 'string', function ($attribute, $value, $fail) use ($user) {
            if (!Hash::check($value, $user->getAuthPassword())) {
                $fail(trans('laravelusers::ui.account_password_invalid'));
            }
        }];

        return $rules + ($this->input('section') === 'email'
            ? ['email' => ['required', 'email', 'max:255', Rule::unique($table, 'email')->ignore($user->getKey(), $user->getKeyName())]]
            : ['password' => PasswordRules::validation(), 'password_confirmation' => ['required', 'string', 'same:password']]);
    }
}
