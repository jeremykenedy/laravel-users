<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\Support;

use Illuminate\Database\Eloquent\Model;

class Avatar
{
    public const SOURCES = ['avatar', 'gravatar', 'initials', 'identicon', 'monsterid', 'robohash', 'retro', 'wavatar', 'mp', 'dicebear', 'ui-avatars'];

    public const GRAVATAR_STYLES = ['identicon', 'monsterid', 'robohash', 'retro', 'wavatar', 'mp'];

    public function __construct(private readonly LocalAvatars $local = new LocalAvatars())
    {
    }

    public function listing(iterable $users): array
    {
        if (!config('laravelusers.avatar.enabled', false)) {
            return [];
        }
        $users = is_array($users) ? $users : iterator_to_array($users);
        $avatars = [];
        $sources = AvatarPreferences::listing($users);
        foreach ($users as $user) {
            $avatars[$user->getKey()] = $this->forUser($user, $sources[$user->getKey()] ?? config('laravelusers.avatar.source', 'initials'));
        }

        return $avatars;
    }

    public function forUser(Model $user, ?string $source = null): array
    {
        $source ??= AvatarPreferences::listing([$user])[$user->getKey()] ?? config('laravelusers.avatar.source', 'initials');
        $size = max(16, min(128, (int) config('laravelusers.avatar.size', 40)));
        $imageSize = max($size, min(1024, (int) config('laravelusers.avatar.image_size', 256)));

        return [
            'src'      => $this->url($user, $source, $imageSize),
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
        if ($source === 'gravatar' || in_array($source, self::GRAVATAR_STYLES, true)) {
            return $this->gravatar($user, $source, $size);
        }
        if (in_array($source, ['dicebear', 'ui-avatars'], true)) {
            $seed = hash_hmac('sha256', implode('|', [get_class($user), $user->getConnectionName() ?? config('database.default'), $user->getTable(), $user->getKey()]), (string) config('app.key'));

            return $this->local->url($source, $this->initials((string) $user->name), $seed, $size);
        }
        if ($source !== 'avatar') {
            return null;
        }
        $url = $user->getAttribute(config('laravelusers.avatar.attribute', 'avatar'));

        return is_string($url) && $this->safeUrl($url) ? $url : null;
    }

    private function gravatar(Model $user, string $source, int $size): ?string
    {
        if (!config('laravelusers.avatar.remote_enabled', true)) {
            return null;
        }
        $default = $source === 'gravatar' ? '404' : $source;

        return 'https://www.gravatar.com/avatar/'.hash('sha256', mb_strtolower(trim((string) $user->email))).'?s='.$size.'&d='.$default.'&r=g'.($source === 'gravatar' ? '' : '&f=y');
    }

    private function safeUrl(string $url): bool
    {
        return preg_match('#^https?://#i', $url) || (str_starts_with($url, '/') && !str_starts_with($url, '//'));
    }
}
