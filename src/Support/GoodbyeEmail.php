<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\Support;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class GoodbyeEmail
{
    public static function allowed(): bool
    {
        $gate = config('laravelusers.emails.gate');

        return Auth::check() && config('laravelusers.emails.enabled', false) && config('laravelusers.emails.goodbye', false)
            && UserAccess::allows('email_goodbye') && (!$gate || Gate::allows($gate));
    }

    public static function rules(): array
    {
        return [
            'send_goodbye'         => ['sometimes', 'boolean', Rule::in(self::allowed() ? [0, 1] : [0])],
            'goodbye'              => ['sometimes', 'array:subject,message,use_greeting,greeting,include_name,use_signoff,signoff,signoff_name'],
            'goodbye.subject'      => ['required_with:goodbye', 'string', 'max:150', 'regex:/^[^\r\n]*$/'],
            'goodbye.message'      => ['required_with:goodbye', 'string', 'max:'.max(1, (int) config('laravelusers.emails.max_length', 10000))],
            'goodbye.use_greeting' => ['sometimes', 'boolean'],
            'goodbye.greeting'     => ['nullable', 'string', 'max:120'],
            'goodbye.include_name' => ['sometimes', 'boolean'],
            'goodbye.use_signoff'  => ['sometimes', 'boolean'],
            'goodbye.signoff'      => ['nullable', 'string', 'max:120'],
            'goodbye.signoff_name' => ['nullable', 'string', 'max:120'],
        ];
    }
}
