<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use jeremykenedy\laravelusers\Support\NativeRuntime;

class NativeSearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return NativeRuntime::name() !== 'blade' && $this->isMethod('GET') && $this->routeIs('users')
            ? ['user_search_box' => ['nullable', 'string', 'max:255']]
            : [];
    }
}
