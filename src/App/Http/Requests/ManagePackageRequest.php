<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use jeremykenedy\laravelusers\Support\ManagedPackages;

class ManagePackageRequest extends FormRequest
{
    public function authorize(ManagedPackages $packages): bool
    {
        return $packages->allowed($this->user());
    }

    public function rules(): array
    {
        return [
            'package'         => ['required', Rule::in(array_merge(array_keys(ManagedPackages::PACKAGES), ['requirements']))],
            'operation'       => ['required', Rule::in($this->input('package') === 'requirements' ? ['setup', 'verify'] : ['install', 'remove', 'configure'])],
            'confirmation'    => [Rule::when($this->input('operation') !== 'verify', ['required', Rule::in([$this->input('operation') === 'remove' ? 'remove' : 'continue'])])],
            'acknowledgement' => [Rule::when($this->input('operation') !== 'verify', ['required', 'accepted'])],
            'setup'           => ['sometimes', 'boolean'],
            'migrate'         => ['sometimes', 'boolean'],
        ];
    }
}
