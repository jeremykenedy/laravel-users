<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\Support;

use Composer\InstalledVersions;
use DiceBear\Avatar as DiceBearAvatar;
use DiceBear\Style;
use Throwable;

class LocalAvatars
{
    private array $styles = [];

    public function url(string $source, string $initials, string $seed, int $size): ?string
    {
        if ($source === 'ui-avatars') {
            return $this->initials($initials, $size);
        }
        $style = (string) config('laravelusers.avatar.dicebear.style', 'identicon');
        if (!preg_match('/\A[a-z][a-z0-9-]{0,63}\z/', $style)) {
            return null;
        }
        $driver = config('laravelusers.avatar.dicebear.driver', 'local');
        if ($driver === 'remote') {
            $base = $this->remoteUrl(config('laravelusers.avatar.dicebear.url'));

            return $base ? rtrim($base, '/').'/'.$style.'/svg?'.http_build_query(['seed' => $seed, 'size' => $size], '', '&', PHP_QUERY_RFC3986) : null;
        }
        if ($driver !== 'local' || !self::diceBearInstalled()) {
            return null;
        }

        try {
            if (!isset($this->styles[$style])) {
                $path = InstalledVersions::getInstallPath('dicebear/styles').'/src/'.$style.'.json';
                if (!is_file($path)) {
                    return null;
                }
                $this->styles[$style] = Style::fromJson(file_get_contents($path));
            }

            return (new DiceBearAvatar($this->styles[$style], ['seed' => $seed, 'size' => $size]))->toDataUri();
        } catch (Throwable $exception) {
            report($exception);

            return null;
        }
    }

    public static function diceBearInstalled(): bool
    {
        return class_exists(DiceBearAvatar::class) && class_exists(Style::class)
            && InstalledVersions::isInstalled('dicebear/styles');
    }

    private function initials(string $initials, int $size): ?string
    {
        $background = $this->color(config('laravelusers.avatar.ui_avatars.background'), 'e7eef8');
        $color = $this->color(config('laravelusers.avatar.ui_avatars.color'), '344760');
        $driver = config('laravelusers.avatar.ui_avatars.driver', 'local');
        if ($driver === 'remote') {
            $base = $this->remoteUrl(config('laravelusers.avatar.ui_avatars.url', 'https://ui-avatars.com/api/'));

            return $base ? $base.'?'.http_build_query(['name' => $initials, 'size' => $size, 'background' => $background, 'color' => $color, 'format' => 'svg'], '', '&', PHP_QUERY_RFC3986) : null;
        }
        if ($driver !== 'local') {
            return null;
        }
        $svg = view('laravelusers::avatars.initials', compact('initials', 'size', 'background', 'color'))->render();

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }

    private function color(mixed $value, string $default): string
    {
        return is_string($value) && preg_match('/\A#?([a-f0-9]{6})\z/i', $value, $matches) ? $matches[1] : $default;
    }

    private function remoteUrl(mixed $url): ?string
    {
        if (!config('laravelusers.avatar.remote_enabled', true) || !is_string($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
            return null;
        }
        $parts = parse_url($url);

        return in_array($parts['scheme'] ?? '', ['http', 'https'], true)
            && !isset($parts['user']) && !isset($parts['pass'])
            && !isset($parts['query']) && !isset($parts['fragment']) ? $url : null;
    }
}
