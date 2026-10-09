<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Route;
use jeremykenedy\laravelusers\Support\EmailContent;

class WelcomeUser extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly string $name, public readonly ?string $resetUrl = null, public readonly array $contents = [])
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
        $defaults = EmailContent::defaults('welcome');
        $copy = $this->contents ? EmailContent::viewData($this->name, array_replace($defaults, $this->contents)) : [];
        $mail = (new MailMessage())
            ->subject($this->contents['subject'] ?? $defaults['subject'])
            ->greeting(trans('laravelusers::ui.welcome_greeting', ['name' => $this->name]))
            ->line($defaults['message']);
        if ($this->resetUrl) {
            $broker = config('laravelusers.welcome.password_broker') ?: config('auth.defaults.passwords');
            $mail->line(trans('laravelusers::ui.reset_notice'))
                ->action(trans('laravelusers::ui.set_password'), $this->resetUrl)
                ->line(trans('laravelusers::ui.reset_expiry', ['minutes' => config('auth.passwords.'.$broker.'.expire', 60)]));
        } elseif (Route::has('login')) {
            $mail->action(trans('laravelusers::ui.sign_in'), route('login'));
        }

        return $mail->markdown('laravelusers::emails.welcome', [
            'name'           => $this->name,
            'appName'        => config('app.name'),
            'resetUrl'       => $this->resetUrl,
            'resetMinutes'   => $this->resetUrl ? config('auth.passwords.'.$broker.'.expire', 60) : null,
            'loginUrl'       => Route::has('login') ? route('login') : null,
            'copy'           => $copy,
            'welcomeMessage' => $defaults['message'],
        ]);
    }
}
