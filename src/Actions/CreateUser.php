<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\Actions;

use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Contracts\Auth\PasswordBroker;
use Illuminate\Contracts\Auth\PasswordBrokerFactory;
use Illuminate\Contracts\Notifications\Dispatcher;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use jeremykenedy\laravelusers\Notifications\WelcomeUser;
use Throwable;

class CreateUser
{
    public function __construct(private readonly PasswordBrokerFactory $passwords, private readonly Dispatcher $notifications)
    {
    }

    public function handle(array $data): bool
    {
        $userModel = config('laravelusers.defaultUserModel');
        $model = new $userModel();
        $reset = (bool) ($data['force_password_reset'] ?? false);
        $broker = $reset ? $this->resetBroker($model) : null;
        [$user, $resetUrl] = $this->createAccount($model, $data, $broker);

        return empty($data['send_welcome_email']) || $this->sendWelcome($user, $resetUrl);
    }

    private function createAccount(Model $model, array $data, ?PasswordBroker $broker): array
    {
        return $model->getConnection()->transaction(function () use ($model, $data, $broker) {
            $user = $model::create([
                'name'     => strip_tags($data['name']),
                'email'    => $data['email'],
                'password' => Hash::make($broker ? Str::random(64) : $data['password']),
            ]);
            if (config('laravelusers.rolesEnabled', false)) {
                $user->attachRole($data['role']);
                $user->save();
            }
            $resetUrl = $broker ? route('password.reset', ['token' => $broker->createToken($user), 'email' => $user->getEmailForPasswordReset()]) : null;

            return [$user, $resetUrl];
        });
    }

    private function sendWelcome(Model $user, ?string $resetUrl): bool
    {
        try {
            $recipient = (new AnonymousNotifiable())->route('mail', $user->email);
            $this->notifications->send($recipient, new WelcomeUser($user->name, $resetUrl));

            return true;
        } catch (Throwable $exception) {
            report($exception);

            return false;
        }
    }

    private function resetBroker(Model $user): PasswordBroker
    {
        $name = config('laravelusers.welcome.password_broker') ?: config('auth.defaults.passwords');
        $provider = config('auth.passwords.'.$name.'.provider');
        $providerModel = config('auth.providers.'.$provider.'.model');
        if (!$user instanceof CanResetPassword || !Route::has('password.reset') || $providerModel !== get_class($user)) {
            throw ValidationException::withMessages(['force_password_reset' => trans('laravelusers::ui.reset_unavailable')]);
        }

        return $this->passwords->broker($name);
    }
}
