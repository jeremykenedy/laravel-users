<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use jeremykenedy\laravelusers\Support\UserAccess;

class UpdateEmailTemplatesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return config('laravelusers.settings.enabled', false) && UserAccess::allows('edit_settings') && UserAccess::allows('edit_email_templates');
    }

    public function rules(): array
    {
        return $this->templateRules() + $this->goodbyeRules();
    }

    private function templateRules(): array
    {
        $rules = [
            'templates'         => ['required', 'array:welcome,reset,restore,force_delete,goodbye'],
            'goodbye'           => ['required', 'boolean'],
            'goodbye_on_delete' => ['required', 'boolean'],
            'welcome_enabled'   => ['required', 'boolean', Rule::in(config('laravelusers.emails.enabled', false) && config('laravelusers.emails.welcome', true) ? [0, 1] : [0])],
        ];
        foreach (['welcome', 'reset', 'restore', 'force_delete', 'goodbye'] as $action) {
            $rules['templates.'.$action] = ['sometimes', 'array:subject,message'];
            $rules['templates.'.$action.'.subject'] = ['required_with:templates.'.$action, 'string', 'max:150', 'regex:/^[^\r\n]*$/'];
            $rules['templates.'.$action.'.message'] = ['required_with:templates.'.$action, 'string', 'max:'.max(1, (int) config('laravelusers.emails.max_length', 10000))];
        }

        return $rules;
    }

    private function goodbyeRules(): array
    {
        $rules = [];
        foreach (['goodbye_auto_send', 'goodbye_retention', 'goodbye_show_expiry'] as $key) {
            $rules[$key] = ['sometimes', 'boolean'];
        }
        foreach (['restore', 'force_delete'] as $action) {
            $rules['goodbye_'.$action] = ['sometimes', 'boolean', Rule::in($this->accountActionAllowed($action) ? [0, 1] : [0])];
        }
        $rules['goodbye_expiry_mode'] = ['sometimes', Rule::in($this->expiryModes())];
        $rules['goodbye_duration'] = ['required_if:goodbye_expiry_mode,custom', 'nullable', 'integer', 'min:1', 'max:525600', function ($attribute, $value, $fail) {
            $unit = $this->input('goodbye_unit');
            $factor = is_string($unit) ? (['minutes' => 1, 'hours' => 60, 'days' => 1440][$unit] ?? 1) : 1;
            if ($this->input('goodbye_expiry_mode', 'custom') === 'custom' && is_numeric($value) && (int) $value * $factor > max(1, min(525600, (int) config('laravelusers.account_links.max_expire', 43200)))) {
                $fail(trans('laravelusers::ui.account_duration_invalid'));
            }
        }];
        $rules['goodbye_unit'] = ['required_with:goodbye_duration', 'nullable', Rule::in(['minutes', 'hours', 'days'])];

        return $rules;
    }

    private function accountActionAllowed(string $action): bool
    {
        return config('laravelusers.softDeletedEnabled', false) && config('laravelusers.account_links.enabled', false)
            && config('laravelusers.account_links.'.$action, true)
            && UserAccess::allows($action === 'restore' ? 'restore_users' : 'force_delete');
    }

    private function expiryModes(): array
    {
        $modes = ['custom'];
        if (config('laravelusers.cleanup.enabled', false)) {
            $modes[] = 'cleanup';
        }
        if (config('laravelusers.account_links.allow_never_expire', true)) {
            $modes[] = 'never';
        }

        return $modes;
    }
}
