<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use jeremykenedy\laravelusers\Support\UserAccess;

class UpdateAccountAccessRequest extends FormRequest
{
    public function authorize(): bool
    {
        return config('laravelusers.settings.enabled', false) && UserAccess::allows('edit_settings') && UserAccess::allows('edit_account_access');
    }

    public function rules(): array
    {
        return [
            'enabled'          => ['required', 'boolean'],
            'settings_enabled' => ['required', 'boolean'],
            'apply_all'        => ['sometimes', Rule::in(['enabled', 'settings_enabled'])],
            'confirmation'     => ['required_with:apply_all', Rule::in(['change'])],
        ];
    }
}
