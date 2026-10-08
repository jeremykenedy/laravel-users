<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\Support;

use Illuminate\Auth\Passwords\CacheTokenRepository;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class ResetTokenCacheRepository extends CacheTokenRepository
{
    public function create(CanResetPassword $user)
    {
        if ($this->expires !== 0) {
            return parent::create($user);
        }
        $this->delete($user);
        $token = hash_hmac('sha256', Str::random(40), $this->hashKey);
        $this->cache->forever($this->cacheKey($user), ['lu0.'.$this->hasher->make($token), Carbon::now()->format($this->format)]);

        return $token;
    }

    public function exists(CanResetPassword $user, $token)
    {
        $record = $this->cache->get($this->cacheKey($user));
        if (!$record) {
            return false;
        }
        if (str_starts_with($record[0], 'lu0.')) {
            return $this->expires === 0 && $this->hasher->check($token, substr($record[0], 4));
        }

        return $this->expires !== 0 && parent::exists($user, $token);
    }
}
