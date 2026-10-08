<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Route;

class WelcomeUser extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly string $name, public readonly ?string $resetUrl = null)
    {
        $this->afterCommit();
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $mail = (new MailMessage())
            ->subject(trans('laravelusers::ui.welcome_subject', ['app' => config('app.name')]))
            ->greeting(trans('laravelusers::ui.welcome_greeting', ['name' => $this->name]))
            ->line(trans('laravelusers::ui.welcome_message', ['app' => config('app.name')]));
        if ($this->resetUrl) {
            $broker = config('laravelusers.welcome.password_broker') ?: config('auth.defaults.passwords');
            $mail->line(trans('laravelusers::ui.reset_notice'))
                ->action(trans('laravelusers::ui.set_password'), $this->resetUrl)
                ->line(trans('laravelusers::ui.reset_expiry', ['minutes' => config('auth.passwords.'.$broker.'.expire', 60)]));
        } elseif (Route::has('login')) {
            $mail->action(trans('laravelusers::ui.sign_in'), route('login'));
        }

        return $mail;
    }
}
