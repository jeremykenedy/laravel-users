<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\Support;

use Illuminate\Auth\Passwords\DatabaseTokenRepository;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Support\Carbon;

class ResetTokenDatabaseRepository extends DatabaseTokenRepository
{
    protected function getPayload($email, $token)
    {
        $payload = parent::getPayload($email, $token);
        if ($this->expires === 0) {
            $payload['token'] = 'lu0.'.$payload['token'];
        }

        return $payload;
    }

    public function exists(CanResetPassword $user, $token)
    {
        $record = (array) $this->getTable()->where('email', $user->getEmailForPasswordReset())->first();
        if ($record && str_starts_with($record['token'], 'lu0.')) {
            return $this->expires === 0 && $this->hasher->check($token, substr($record['token'], 4));
        }

        return $this->expires !== 0 && parent::exists($user, $token);
    }

    public function deleteExpired()
    {
        $this->getTable()->where('token', 'not like', 'lu0.%')->where('created_at', '<', Carbon::now()->subSeconds($this->expires))->delete();
    }
}
