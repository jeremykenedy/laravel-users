<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\Support;

use Illuminate\Database\Eloquent\Model;

class Avatar
{
    public function listing(iterable $users): array
    {
        if (!config('laravelusers.avatar.enabled', false)) {
            return [];
        }
        $avatars = [];
        foreach ($users as $user) {
            $avatars[$user->getKey()] = $this->forUser($user);
        }

        return $avatars;
    }

    public function forUser(Model $user): array
    {
        $source = config('laravelusers.avatar.source', 'initials');
        $size = max(16, min(128, (int) config('laravelusers.avatar.size', 40)));
        $words = preg_split('/\s+/u', trim((string) $user->name), -1, PREG_SPLIT_NO_EMPTY);
        $initials = '';
        foreach (array_slice($words ?: [], 0, 2) as $word) {
            $initials .= mb_strtoupper(mb_substr($word, 0, 1));
        }
        $src = null;
        if ($source === 'gravatar') {
            $src = 'https://www.gravatar.com/avatar/'.hash('sha256', mb_strtolower(trim((string) $user->email))).'?s='.$size.'&d=404&r=g';
        } elseif ($source === 'avatar') {
            $src = $user->getAttribute(config('laravelusers.avatar.attribute', 'avatar'));
            $src = is_string($src) && (preg_match('#^https?://#i', $src) || (str_starts_with($src, '/') && !str_starts_with($src, '//'))) ? $src : null;
        }

        return ['src' => $src, 'initials' => $initials ?: '?', 'size' => $size, 'fallback' => $source === 'initials' ? 'initials' : config('laravelusers.avatar.fallback', 'icon')];
    }
}
