<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use jeremykenedy\laravelusers\Support\EmailContent;

class ResetUserPassword extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly string $name, public readonly string $url, public readonly int $minutes, public readonly array $contents = [])
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
        $defaults = EmailContent::defaults('reset');
        $copy = $this->contents ? EmailContent::viewData($this->name, array_replace($defaults, $this->contents)) : ['greeting' => trans('laravelusers::ui.welcome_greeting', ['name' => $this->name]), 'body' => $defaults['message'], 'signoff' => config('app.name')];

        return (new MailMessage())->subject($this->contents['subject'] ?? $defaults['subject'])
            ->greeting(trans('laravelusers::ui.welcome_greeting', ['name' => $this->name]))
            ->line(trans('laravelusers::ui.reset_requested'))
            ->action(trans('laravelusers::ui.reset_password'), $this->url)
            ->line($this->minutes === 0 ? trans('laravelusers::ui.reset_never_expiry') : trans('laravelusers::ui.reset_expiry', ['minutes' => $this->minutes]))
            ->markdown('laravelusers::emails.reset-password', array_merge($copy, ['url' => $this->url, 'minutes' => $this->minutes]));
    }
}
