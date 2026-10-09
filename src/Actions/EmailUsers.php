<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\Actions;

use Illuminate\Auth\Passwords\PasswordBroker as NativePasswordBroker;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Contracts\Auth\PasswordBroker;
use Illuminate\Contracts\Auth\PasswordBrokerFactory;
use Illuminate\Contracts\Notifications\Dispatcher;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use jeremykenedy\laravelusers\Notifications\ResetUserPassword;
use jeremykenedy\laravelusers\Notifications\UserMessage;
use jeremykenedy\laravelusers\Notifications\WelcomeUser;
use jeremykenedy\laravelusers\Support\AccountLinks;
use jeremykenedy\laravelusers\Support\EmailRecipients;
use jeremykenedy\laravelusers\Support\ExpiringTokenRepository;
use Throwable;

class EmailUsers
{
    public function __construct(private readonly PasswordBrokerFactory $passwords, private readonly Dispatcher $notifications, private readonly EmailRecipients $recipients, private readonly AccountLinks $links)
    {
    }

    public function handle(array $data): int
    {
        $users = $this->recipients->get($data);
        $broker = $data['action'] === 'reset' ? $this->resetBroker($users->first()) : null;
        $minutes = $this->resetMinutes($data);
        $this->validateResetExpiry($broker, $minutes);
        $actions = array_keys(array_filter(['restore' => !empty($data['include_restore']), 'force_delete' => !empty($data['include_force_delete'])]));
        $sent = 0;
        foreach ($users as $user) {
            try {
                if ($broker) {
                    $this->sendReset($user, $broker, $minutes, $data);
                } else {
                    $this->sendMessage($user, $data, $actions);
                }
                $sent++;
            } catch (ValidationException $exception) {
                throw $exception;
            } catch (Throwable $exception) {
                report($exception);

                throw ValidationException::withMessages(['email' => trans_choice('laravelusers::ui.email_failed', $sent, ['count' => $sent])]);
            }
        }

        return $sent;
    }

    private function resetMinutes(array $data): ?int
    {
        if (!empty($data['reset_never_expire'])) {
            return 0;
        }
        if (!isset($data['reset_duration'])) {
            return null;
        }

        return (int) $data['reset_duration'] * ['minutes' => 1, 'hours' => 60, 'days' => 1440][$data['reset_unit']];
    }

    private function validateResetExpiry(?PasswordBroker $broker, ?int $minutes): void
    {
        if ($broker === null || $minutes === null) {
            return;
        }
        if (!$broker instanceof NativePasswordBroker || !$broker->getRepository() instanceof ExpiringTokenRepository) {
            throw ValidationException::withMessages(['reset_duration' => trans('laravelusers::ui.reset_duration_unavailable')]);
        }
    }

    private function sendMessage(Model $user, array $data, array $actions): void
    {
        $links = $this->accountLinks($user, $data, $actions);
        $notification = $data['action'] === 'welcome'
            ? new WelcomeUser($user->name, null, $this->contents($data))
            : new UserMessage($user->name, $data, $links);
        $this->notifications->send((new AnonymousNotifiable())->route('mail', $user->email), $notification);
    }

    private function accountLinks(Model $user, array $data, array $actions): array
    {
        if (!$actions || empty($data['deleted'])) {
            return [];
        }
        $minutes = !empty($data['account_never_expire']) ? 0 : (int) $data['account_duration'] * ['minutes' => 1, 'hours' => 60, 'days' => 1440][$data['account_unit']];

        return $this->links->issue($user, $actions, $minutes);
    }

    private function resetBroker(Model $user): PasswordBroker
    {
        $name = config('laravelusers.emails.password_broker') ?: config('auth.defaults.passwords');
        $provider = config('auth.passwords.'.$name.'.provider');
        if (!$user instanceof CanResetPassword || !Route::has('password.reset') || config('auth.providers.'.$provider.'.model') !== get_class($user)) {
            throw ValidationException::withMessages(['email' => trans('laravelusers::ui.reset_unavailable')]);
        }

        return $this->passwords->broker($name);
    }

    private function contents(array $data): array
    {
        return isset($data['message']) || isset($data['subject']) ? $data : [];
    }

    private function sendReset(Model $user, PasswordBroker $broker, ?int $minutes, array $data): void
    {
        $name = config('laravelusers.emails.password_broker') ?: config('auth.defaults.passwords');
        $send = fn () => $broker->sendResetLink(['email' => $user->getEmailForPasswordReset()], function ($recipient, $token) use ($name, $minutes, $data) {
            $url = route('password.reset', ['token' => $token, 'email' => $recipient->getEmailForPasswordReset()]);
            $this->notifications->send((new AnonymousNotifiable())->route('mail', $recipient->getEmailForPasswordReset()), new ResetUserPassword($recipient->name, $url, $minutes ?? (int) config('auth.passwords.'.$name.'.expire', 60), $this->contents($data)));
        });
        $status = $minutes === null ? $send() : $broker->getRepository()->withExpiry($minutes, $send);
        if ($status !== PasswordBroker::RESET_LINK_SENT) {
            throw ValidationException::withMessages(['email' => trans($status)]);
        }
    }
}
