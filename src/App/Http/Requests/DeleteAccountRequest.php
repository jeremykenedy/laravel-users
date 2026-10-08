<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\App\Http\Requests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use jeremykenedy\laravelusers\Support\AccountPreferences;

class DeleteAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() instanceof Model && AccountPreferences::editable($this->user()) && config('laravelusers.account.delete', true);
    }

    public function rules(): array
    {
        return [
            'confirmation'     => ['required', Rule::in(['delete'])],
            'current_password' => ['required', 'string', function ($attribute, $value, $fail) {
                if (!Hash::check($value, $this->user()->getAuthPassword())) {
                    $fail(trans('laravelusers::ui.account_password_invalid'));
                }
            }],
        ];
    }
}
