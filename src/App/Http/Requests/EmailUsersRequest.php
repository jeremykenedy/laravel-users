<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class EmailUsersRequest extends FormRequest
{
    public function authorize(): bool
    {
        $gate = config('laravelusers.emails.gate');

        return Auth::check() && config('laravelusers.emails.enabled', false) && (!$gate || Gate::allows($gate));
    }

    public function rules(): array
    {
        $deleted = $this->boolean('deleted');
        $actions = array_filter(['message', 'reset', 'welcome'], fn ($action) => config('laravelusers.emails.'.$action, true) && ($action !== 'welcome' || config('laravelusers.welcome.enabled', false)));

        return $this->recipientRules($deleted, $actions) + $this->accountLinkRules($deleted) + $this->resetRules() + $this->contentRules();
    }

    private function recipientRules(bool $deleted, array $actions): array
    {
        return [
            'action' => ['required', Rule::in($deleted ? ['message'] : $actions), function ($attribute, $value, $fail) use ($deleted, $actions) {
                if ($deleted && (!config('laravelusers.emails.deleted', true) || !config('laravelusers.softDeletedEnabled', false) || !in_array('message', $actions, true))) {
                    $fail(trans('laravelusers::ui.email_invalid_selection'));
                }
            }],
            'ids' => ['required', 'array', 'min:1', 'max:'.max(1, min(1000, (int) config('laravelusers.bulkLimit', 100))), function ($attribute, $value, $fail) {
                if (is_array($value) && count($value) > 1 && (!config('laravelusers.bulkActions', false) || !config('laravelusers.emails.bulk', true))) {
                    $fail(trans('laravelusers::ui.email_bulk_disabled'));
                }
            }],
            'ids.*' => ['required', 'distinct', function ($attribute, $value, $fail) {
                if ((!is_string($value) && !is_int($value)) || strlen((string) $value) > 255) {
                    $fail(trans('laravelusers::ui.email_invalid_selection'));
                }
            }],
            'deleted' => ['sometimes', 'boolean'],
        ];
    }

    private function accountLinkRules(bool $deleted): array
    {
        $links = $this->boolean('include_restore') || $this->boolean('include_force_delete');
        $never = $this->boolean('account_never_expire');
        $enabled = $deleted && config('laravelusers.account_links.enabled', false);

        return [
            'account_never_expire' => ['sometimes', 'boolean', Rule::in($enabled && $links && config('laravelusers.account_links.allow_never_expire', true) ? [0, 1] : [0])],
            'include_restore'      => ['sometimes', 'boolean', Rule::in($enabled && config('laravelusers.account_links.restore', true) ? [0, 1] : [0])],
            'include_force_delete' => ['sometimes', 'boolean', Rule::in($enabled && config('laravelusers.account_links.force_delete', true) ? [0, 1] : [0])],
            'account_duration'     => [Rule::when($never, 'exclude'), $links ? 'required' : 'nullable', 'integer', 'min:1', 'max:'.max(1, min(525600, (int) config('laravelusers.account_links.max_expire', 43200))), function ($attribute, $value, $fail) {
                if ($this->accountDurationExceeded($value)) {
                    $fail(trans('laravelusers::ui.account_duration_invalid'));
                }
            }],
            'account_unit' => [Rule::when($never, 'exclude'), 'required_with:account_duration', 'nullable', Rule::in(['minutes', 'hours', 'days'])],
        ];
    }

    private function accountDurationExceeded(mixed $value): bool
    {
        $unit = $this->input('account_unit');
        $factor = is_string($unit) ? (['minutes' => 1, 'hours' => 60, 'days' => 1440][$unit] ?? 1) : 1;

        return is_numeric($value) && (int) $value * $factor > max(1, min(525600, (int) config('laravelusers.account_links.max_expire', 43200)));
    }

    private function resetRules(): array
    {
        return [
            'reset_never_expire' => ['sometimes', 'boolean', Rule::in($this->input('action') === 'reset' && config('laravelusers.emails.reset_duration', true) && config('laravelusers.emails.reset_allow_never_expire', true) ? [0, 1] : [0])],
            'reset_duration'     => [Rule::when($this->boolean('reset_never_expire'), 'exclude'), 'nullable', 'integer', 'min:1', 'max:'.max(1, min(525600, (int) config('laravelusers.emails.reset_max_expire', 43200))), function ($attribute, $value, $fail) {
                $unit = $this->input('reset_unit', 'minutes');
                $factor = is_string($unit) ? (['minutes' => 1, 'hours' => 60, 'days' => 1440][$unit] ?? 1) : 1;
                if (is_numeric($value) && (!config('laravelusers.emails.reset_duration', true) || (int) $value * $factor > max(1, min(525600, (int) config('laravelusers.emails.reset_max_expire', 43200))))) {
                    $fail(trans('laravelusers::ui.reset_duration_invalid'));
                }
            }],
            'reset_unit' => [Rule::when($this->boolean('reset_never_expire'), 'exclude'), 'required_with:reset_duration', 'nullable', Rule::in(['minutes', 'hours', 'days'])],
        ];
    }

    private function contentRules(): array
    {
        $editable = in_array($this->input('action'), ['welcome', 'reset'], true) && !config('laravelusers.emails.edit_'.$this->input('action'), true);

        return [
            'subject'      => [Rule::when($editable, 'prohibited'), 'required_if:action,message', 'nullable', 'string', 'max:150', 'regex:/^[^\r\n]*$/'],
            'message'      => [Rule::when($editable, 'prohibited'), 'required_if:action,message', 'nullable', 'string', 'max:'.max(1, (int) config('laravelusers.emails.max_length', 10000))],
            'use_greeting' => [Rule::when($editable, 'prohibited'), 'sometimes', 'boolean'],
            'greeting'     => [Rule::when($editable, 'prohibited'), 'nullable', 'string', 'max:120'],
            'include_name' => [Rule::when($editable, 'prohibited'), 'sometimes', 'boolean'],
            'use_signoff'  => [Rule::when($editable, 'prohibited'), 'sometimes', 'boolean'],
            'signoff'      => [Rule::when($editable, 'prohibited'), 'nullable', 'string', 'max:120'],
            'signoff_name' => [Rule::when($editable, 'prohibited'), 'nullable', 'string', 'max:120'],
        ];
    }
}
