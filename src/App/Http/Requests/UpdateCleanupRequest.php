<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use jeremykenedy\laravelusers\Support\UserAccess;

class UpdateCleanupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return config('laravelusers.settings.enabled', false) && UserAccess::allows('edit_settings') && UserAccess::allows('edit_cleanup') && UserAccess::allows('force_delete');
    }

    public function rules(): array
    {
        return [
            'enabled'      => ['required', 'boolean'],
            'unit'         => ['required', Rule::in(['immediately', 'minutes', 'hours', 'days', 'months', 'years'])],
            'amount'       => ['required_unless:unit,immediately', 'nullable', 'integer', 'min:1', 'max:10000'],
            'confirmation' => [$this->boolean('enabled') ? 'required' : 'nullable', Rule::in(['permanently delete'])],
        ];
    }
}
