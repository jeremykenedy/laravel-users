<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\Actions;

use Illuminate\Contracts\View\Factory;
use Illuminate\Mail\Markdown;
use jeremykenedy\laravelusers\Notifications\ResetUserPassword;
use jeremykenedy\laravelusers\Notifications\UserMessage;
use jeremykenedy\laravelusers\Notifications\WelcomeUser;
use jeremykenedy\laravelusers\Support\EmailRecipients;

class PreviewUserEmail
{
    public function __construct(private readonly EmailRecipients $recipients, private readonly Markdown $markdown, private readonly Factory $views)
    {
    }

    public function handle(array $data): array
    {
        abort_unless(config('laravelusers.emails.preview', true), 403);
        $user = $this->recipients->get($data)->first();
        $links = [];
        foreach (['restore', 'force_delete'] as $action) {
            if (!empty($data['deleted']) && !empty($data['include_'.$action])) {
                $links[$action] = '#'.$action;
            }
        }
        $broker = config('laravelusers.emails.password_broker') ?: config('auth.defaults.passwords');
        $minutes = !empty($data['reset_never_expire']) ? 0 : (isset($data['reset_duration']) ? (int) $data['reset_duration'] * ['minutes' => 1, 'hours' => 60, 'days' => 1440][$data['reset_unit']] : (int) config('auth.passwords.'.$broker.'.expire', 60));
        $contents = isset($data['message']) || isset($data['subject']) ? $data : [];
        $notification = match ($data['action']) {
            'reset'   => new ResetUserPassword($user->name, '#reset-password', $minutes, $contents),
            'welcome' => new WelcomeUser($user->name, null, $contents),
            default   => new UserMessage($user->name, $data, $links),
        };
        $mail = $notification->toMail($user);
        $html = $mail->markdown ? (string) $this->markdown->render($mail->markdown, $mail->data()) : $this->views->make($mail->view['html'], $mail->data())->render();

        return ['html' => $html, 'recipient' => $user->name];
    }
}
