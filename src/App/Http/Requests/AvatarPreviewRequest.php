<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use jeremykenedy\laravelusers\Actions\AvatarPreview;
use jeremykenedy\laravelusers\Support\Avatar;
use jeremykenedy\laravelusers\Support\UserAccess;

class AvatarPreviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && config('laravelusers.settings.enabled', false)
            && UserAccess::allows('edit_settings') && UserAccess::allows('edit_appearance');
    }

    public function rules(): array
    {
        return $this->isMethod('GET')
            ? ['sample' => ['required', Rule::in(array_keys(AvatarPreview::SAMPLES))]]
            : ['avatar_source' => ['required', Rule::in(Avatar::SOURCES)]];
    }

    protected function prepareForValidation(): void
    {
        if ($this->isMethod('GET')) {
            $this->merge(['sample' => $this->route('sample')]);
        }
    }
}
