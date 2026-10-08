<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\App\Http\Requests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use jeremykenedy\laravelusers\Support\RoleAccess;
use jeremykenedy\laravelusers\Support\UserAccess;

class UpdateImpersonationSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return config('laravelusers.settings.enabled', false)
            && config('laravelusers.settings.packages.enabled', false)
            && $user instanceof Model
            && UserAccess::allows('edit_settings', actor: $user)
            && Gate::forUser($user)->allows(config('laravelusers.settings.packages.gate', 'manage-laravelusers-packages'))
            && RoleAccess::available($user);
    }

    public function rules(): array
    {
        return ['enabled' => ['required', 'boolean']];
    }
}
