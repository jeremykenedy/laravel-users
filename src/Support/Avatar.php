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

        return [
            'src'      => $this->url($user, $source, $size),
            'initials' => $this->initials((string) $user->name),
            'size'     => $size,
            'fallback' => $source === 'initials' ? 'initials' : config('laravelusers.avatar.fallback', 'icon'),
        ];
    }

    private function initials(string $name): string
    {
        $words = preg_split('/\s+/u', trim($name), -1, PREG_SPLIT_NO_EMPTY);
        $initials = '';
        foreach (array_slice($words ?: [], 0, 2) as $word) {
            $initials .= mb_strtoupper(mb_substr($word, 0, 1));
        }

        return $initials ?: '?';
    }

    private function url(Model $user, string $source, int $size): ?string
    {
        if ($source === 'gravatar') {
            return 'https://www.gravatar.com/avatar/'.hash('sha256', mb_strtolower(trim((string) $user->email))).'?s='.$size.'&d=404&r=g';
        }
        if ($source !== 'avatar') {
            return null;
        }
        $url = $user->getAttribute(config('laravelusers.avatar.attribute', 'avatar'));

        return is_string($url) && $this->safeUrl($url) ? $url : null;
    }

    private function safeUrl(string $url): bool
    {
        return preg_match('#^https?://#i', $url) || (str_starts_with($url, '/') && !str_starts_with($url, '//'));
    }
}
