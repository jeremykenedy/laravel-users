<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ConfirmEmailChange extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly string $name, public readonly string $url, public readonly string $side, public readonly int $minutes)
    {
        $this->afterCommit();
    }

    /**
     * Laravel passes the notifiable to every notification channel callback.
     *
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     */
    public function via($notifiable): array
    {
        return ['mail'];
    }

    /**
     * Laravel passes the notifiable to every notification channel callback.
     *
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     */
    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage())->subject(trans('laravelusers::ui.account_email_subject'))
            ->greeting(trans('laravelusers::ui.welcome_greeting', ['name' => $this->name]))
            ->line(trans('laravelusers::ui.account_email_notice_'.$this->side))
            ->line(trans('laravelusers::ui.account_email_both'))
            ->action(trans('laravelusers::ui.account_email_confirm'), $this->url)
            ->line(trans('laravelusers::ui.reset_expiry', ['minutes' => $this->minutes]))
            ->line(trans('laravelusers::ui.account_email_ignore'));
    }
}
