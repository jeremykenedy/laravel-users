<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use jeremykenedy\laravelusers\Support\GoodbyeEmail;

class BulkUsersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) config('laravelusers.bulkActions', false);
    }

    public function rules(): array
    {
        return [
            'action' => ['required', Rule::in(['delete', 'restore', 'force_delete'])],
            'ids'    => ['required', 'array', 'min:1', 'max:'.max(1, min(1000, (int) config('laravelusers.bulkLimit', 100)))],
            'ids.*'  => ['required', 'distinct', function ($attribute, $value, $fail) {
                if ((!is_int($value) && !is_string($value)) || mb_strlen((string) $value) > 255) {
                    $fail(trans('laravelusers::ui.invalid_selection'));
                }
            }],
        ] + GoodbyeEmail::rules();
    }
}
