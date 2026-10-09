<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\Actions;

use Illuminate\Contracts\View\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Mail\Markdown;
use Illuminate\Notifications\Notification;
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
        $mail = $this->notification($user, $data)->toMail($user);
        $html = $mail->markdown ? (string) $this->markdown->render($mail->markdown, $mail->data()) : $this->views->make($mail->view['html'], $mail->data())->render();

        return ['html' => $html, 'recipient' => $user->name];
    }

    private function notification(Model $user, array $data): Notification
    {
        $contents = isset($data['message']) || isset($data['subject']) ? $data : [];

        return match ($data['action']) {
            'reset'   => new ResetUserPassword($user->name, '#reset-password', $this->resetMinutes($data), $contents),
            'welcome' => new WelcomeUser($user->name, null, $contents),
            default   => new UserMessage($user->name, $data, $this->accountLinks($data)),
        };
    }

    private function accountLinks(array $data): array
    {
        $links = [];
        foreach (['restore', 'force_delete'] as $action) {
            if (!empty($data['deleted']) && !empty($data['include_'.$action])) {
                $links[$action] = '#'.$action;
            }
        }

        return $links;
    }

    private function resetMinutes(array $data): int
    {
        if (!empty($data['reset_never_expire'])) {
            return 0;
        }
        if (isset($data['reset_duration'])) {
            return (int) $data['reset_duration'] * ['minutes' => 1, 'hours' => 60, 'days' => 1440][$data['reset_unit']];
        }
        $broker = config('laravelusers.emails.password_broker') ?: config('auth.defaults.passwords');

        return (int) config('auth.passwords.'.$broker.'.expire', 60);
    }
}
