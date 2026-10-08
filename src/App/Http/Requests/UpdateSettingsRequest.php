<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\App\Http\Requests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use jeremykenedy\laravelusers\Support\Avatar;
use jeremykenedy\laravelusers\Support\RoleAccess;
use jeremykenedy\laravelusers\Support\UserAccess;
use jeremykenedy\laravelusers\Support\UserNotifications;

class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return UserAccess::allows('edit_settings');
    }

    public function rules(): array
    {
        $user = $this->user();
        $roles = $user instanceof Model && RoleAccess::available($user);
        $rules = [
            'profile_dark_color'             => [UserAccess::allows('edit_appearance') ? 'sometimes' : 'prohibited', 'string', 'regex:/\A#[a-f0-9]{6}\z/i'],
            'edit_dark_color'                => [UserAccess::allows('edit_appearance') ? 'sometimes' : 'prohibited', 'string', 'regex:/\A#[a-f0-9]{6}\z/i'],
            'profile_dark_gradient'          => [UserAccess::allows('edit_appearance') ? 'sometimes' : 'prohibited', 'boolean'],
            'edit_dark_gradient'             => [UserAccess::allows('edit_appearance') ? 'sometimes' : 'prohibited', 'boolean'],
            'profile_dark_gradient_strength' => [UserAccess::allows('edit_appearance') ? 'sometimes' : 'prohibited', 'integer', 'min:0', 'max:100'],
            'edit_dark_gradient_strength'    => [UserAccess::allows('edit_appearance') ? 'sometimes' : 'prohibited', 'integer', 'min:0', 'max:100'],
            'profile_gradient'               => [UserAccess::allows('edit_appearance') ? 'sometimes' : 'prohibited', 'boolean'],
            'edit_gradient'                  => [UserAccess::allows('edit_appearance') ? 'sometimes' : 'prohibited', 'boolean'],
            'profile_gradient_strength'      => [UserAccess::allows('edit_appearance') ? 'sometimes' : 'prohibited', 'integer', 'min:0', 'max:100'],
            'edit_gradient_strength'         => [UserAccess::allows('edit_appearance') ? 'sometimes' : 'prohibited', 'integer', 'min:0', 'max:100'],
            'avatar_source'                  => [UserAccess::allows('edit_appearance') ? 'required' : 'prohibited', Rule::in(Avatar::SOURCES)],
            'profile_color'                  => [UserAccess::allows('edit_appearance') ? 'required' : 'prohibited', 'string', 'regex:/\A#[a-f0-9]{6}\z/i'],
            'edit_color'                     => [UserAccess::allows('edit_appearance') ? 'required' : 'prohibited', 'string', 'regex:/\A#[a-f0-9]{6}\z/i'],
            'notifications_driver'           => [UserAccess::allows('edit_notifications') ? 'sometimes' : 'prohibited', Rule::in(UserNotifications::toastInstalled() ? ['alert', 'toast'] : ['alert'])],
            'notifications_dismissible'      => [UserAccess::allows('edit_notifications') ? 'sometimes' : 'prohibited', 'boolean'],
            'access'                         => [$roles ? 'sometimes' : 'prohibited', 'array:'.implode(',', UserAccess::ACTIONS)],
        ];
        if (!$roles) {
            return $rules;
        }

        return $rules + [
            'access.*'               => ['required', 'array:mode,roles,permissions,level'],
            'access.*.mode'          => ['required', Rule::in(['inherit', 'restricted', 'deny'])],
            'access.*.roles'         => ['sometimes', 'array', 'max:1000'],
            'access.*.roles.*'       => ['required', 'distinct', Rule::in(RoleAccess::query($user, 'role')->get()->modelKeys())],
            'access.*.permissions'   => ['sometimes', 'array', 'max:1000'],
            'access.*.permissions.*' => ['required', 'distinct', Rule::in(RoleAccess::query($user, 'permission')->get()->modelKeys())],
            'access.*.level'         => [method_exists($user, 'level') ? 'nullable' : 'prohibited', 'integer', 'min:1', 'max:100000'],
        ];
    }
}
