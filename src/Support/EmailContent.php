<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\Support;

class EmailContent
{
    public static function defaults(string $action): array
    {
        $subject = match ($action) {
            'welcome'                            => trans('laravelusers::ui.welcome_subject', ['app' => config('app.name')]),
            'restore', 'force_delete', 'goodbye' => trans('laravelusers::ui.'.$action.'_email_subject', ['app' => config('app.name')]),
            default                              => trans('laravelusers::ui.reset_subject'),
        };
        $message = match ($action) {
            'welcome'                            => trans('laravelusers::ui.welcome_message', ['app' => config('app.name')]),
            'restore', 'force_delete', 'goodbye' => trans('laravelusers::ui.'.$action.'_email_message', ['app' => config('app.name')]),
            default                              => trans('laravelusers::ui.reset_requested'),
        };

        return ['subject' => config('laravelusers.emails.'.$action.'_subject') ?? $subject, 'message' => config('laravelusers.emails.'.$action.'_message') ?? $message];
    }

    public static function viewData(string $name, array $contents): array
    {
        $greeting = !empty($contents['use_greeting']) ? trim($contents['greeting'] ?? '') : '';
        if ($greeting !== '' && !empty($contents['include_name'])) {
            $greeting .= ' '.$name.',';
        }
        $signoff = !empty($contents['use_signoff']) ? trim($contents['signoff'] ?? '') : '';
        if ($signoff !== '' && !empty($contents['signoff_name'])) {
            $signoff .= "\n".$contents['signoff_name'];
        }

        return ['greeting' => $greeting, 'body' => $contents['message'], 'signoff' => $signoff];
    }
}
