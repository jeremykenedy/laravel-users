<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\Support;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use RuntimeException;
use Throwable;

class InstallationConfiguration
{
    public function __construct(private Command $command, private bool $interactive)
    {
    }

    public function saveNotifications(Filesystem $files, ?string $driver): void
    {
        if ($driver === null) {
            return;
        }
        $settings = array_replace(config('laravelusers-notifications', []), ['driver' => $driver]);
        $code = $this->exportSettings($settings, ['driver' => 'LARAVEL_USERS_NOTIFICATIONS_DRIVER', 'dismissible' => 'LARAVEL_USERS_NOTIFICATIONS_DISMISSIBLE']);
        $files->replace(config_path('laravelusers-notifications.php'), "<?php\n\nreturn ".$code.";\n");
    }

    public function saveRoles(Filesystem $files, array $settings): void
    {
        if ($settings === []) {
            return;
        }
        $environment = ['rolesEnabled' => 'LARAVEL_USERS_ROLES_ENABLED', 'roleModel' => 'LARAVEL_USERS_ROLE_MODEL', 'rolesMiddlwareEnabled' => 'LARAVEL_USERS_ROLES_MIDDLWARE_ENABLED', 'rolesMiddlware' => 'LARAVEL_USERS_ROLES_MIDDLWARE'];
        $this->saveRoleEnvironment($files, $settings, $environment);
        $settings = array_merge(config('laravelusers-roles', []), $settings);
        $lines = [];
        foreach ($settings as $key => $value) {
            $export = var_export($value, true);
            if (isset($environment[$key])) {
                $export = "env('".$environment[$key]."', ".$export.')';
            }
            $lines[] = '    '.var_export($key, true).' => '.$export.',';
        }
        $files->replace(config_path('laravelusers-roles.php'), "<?php\n\nreturn [\n".implode("\n", $lines)."\n];\n");
    }

    private function saveRoleEnvironment(Filesystem $files, array $settings, array $environment): void
    {
        $path = $this->command->getLaravel()->environmentFilePath();
        if (!$files->isFile($path)) {
            return;
        }
        $contents = $files->get($path);
        foreach ($settings as $key => $value) {
            if (!isset($environment[$key])) {
                continue;
            }
            $name = $environment[$key];
            $pattern = '/^\h*(?:export\h+)?'.preg_quote($name, '/').'\h*=[^\r\n]*(?:\r?\n|$)/m';
            if (is_array($value)) {
                $contents = preg_replace($pattern, '', $contents);

                continue;
            }
            $literal = is_bool($value) ? ($value ? 'true' : 'false') : '"'.strtr((string) $value, ['\\' => '\\\\', '"' => '\\"', '$' => '\\$', "\r" => '\\r', "\n" => '\\n']).'"';
            $entry = $name.'='.$literal.PHP_EOL;
            $contents = preg_match($pattern, $contents) ? preg_replace_callback($pattern, fn () => $entry, $contents) : rtrim($contents, "\r\n").PHP_EOL.$entry;
        }
        $this->replaceEnvironment($files, $path, $contents);
    }

    /**
     * Filesystem failures are checked and reported together while preserving atomic replacement.
     *
     * @SuppressWarnings("PHPMD.ErrorControlOperator")
     */
    private function replaceEnvironment(Filesystem $files, string $path, string $contents): void
    {
        clearstatcache(true, $path);
        $path = realpath($path) ?: $path;
        $temporary = null;
        $message = 'Unable to update the environment file. Check its file and directory permissions, then retry.';

        try {
            $mode = @fileperms($path);
            $temporary = @tempnam(dirname($path), '.laravelusers-env-');
            if ($mode === false || $temporary === false || dirname($temporary) !== dirname($path)
                || !$this->writeEnvironment($files, $temporary, $path, $contents, $mode)) {
                throw new RuntimeException($message);
            }
        } catch (Throwable $exception) {
            $this->command->error($message);

            throw new RuntimeException($message, 0, $exception);
        } finally {
            if (is_string($temporary) && is_file($temporary)) {
                @unlink($temporary);
            }
        }
    }

    public function saveConfiguration(Filesystem $files, string $framework, string $theme, string $runtime): void
    {
        $files->ensureDirectoryExists(config_path());
        $config = config_path('laravelusers.php');
        if (!$files->exists($config)) {
            $files->copy(dirname(__DIR__).'/config/laravelusers.php', $config);
        }

        $settings = array_merge(config('laravelusers-ui', []), ['framework' => $framework, 'theme' => $theme]);
        if ($this->command->option('frontend') !== null || $this->interactive || array_key_exists('runtime', $settings)) {
            $settings['runtime'] = $runtime;
        }
        $environment = ['framework' => 'LARAVEL_USERS_FRONTEND', 'theme' => 'LARAVEL_USERS_THEME', 'runtime' => 'LARAVEL_USERS_RUNTIME'];
        $lines = [];
        foreach ($settings as $key => $value) {
            $default = var_export($value, true);
            $export = isset($environment[$key]) ? "env('".$environment[$key]."', ".$default.')' : $default;
            $lines[] = '    '.var_export($key, true).' => '.$export.',';
        }
        $files->replace(config_path('laravelusers-ui.php'), "<?php\n\nreturn [\n".implode("\n", $lines)."\n];\n");
    }

    public function saveAvatars(Filesystem $files, array $settings): void
    {
        if ($settings === []) {
            return;
        }
        $settings = array_replace_recursive(config('laravelusers-avatar', []), $settings);
        $environment = ['source' => 'LARAVEL_USERS_AVATAR_SOURCE', 'enabled' => 'LARAVEL_USERS_AVATAR_ENABLED', 'per_user' => 'LARAVEL_USERS_AVATAR_PER_USER', 'attribute' => 'LARAVEL_USERS_AVATAR_ATTRIBUTE', 'fallback' => 'LARAVEL_USERS_AVATAR_FALLBACK', 'size' => 'LARAVEL_USERS_AVATAR_SIZE', 'image_size' => 'LARAVEL_USERS_AVATAR_IMAGE_SIZE', 'remote_enabled' => 'LARAVEL_USERS_AVATAR_REMOTE_ENABLED', 'dicebear.driver' => 'LARAVEL_USERS_AVATAR_DICEBEAR_DRIVER', 'dicebear.style' => 'LARAVEL_USERS_AVATAR_DICEBEAR_STYLE', 'dicebear.url' => 'LARAVEL_USERS_AVATAR_DICEBEAR_URL', 'ui_avatars.driver' => 'LARAVEL_USERS_AVATAR_UI_DRIVER', 'ui_avatars.url' => 'LARAVEL_USERS_AVATAR_UI_URL', 'ui_avatars.background' => 'LARAVEL_USERS_AVATAR_UI_BACKGROUND', 'ui_avatars.color' => 'LARAVEL_USERS_AVATAR_UI_COLOR'];
        $files->replace(config_path('laravelusers-avatar.php'), "<?php\n\nreturn ".$this->exportSettings($settings, $environment).";\n");
    }

    private function exportSettings(array $settings, array $environment, string $prefix = ''): string
    {
        $lines = [];
        foreach ($settings as $key => $value) {
            $path = $prefix.$key;
            $export = is_array($value) ? $this->exportSettings($value, $environment, $path.'.') : var_export($value, true);
            if (isset($environment[$path])) {
                $export = "env('".$environment[$path]."', ".$export.')';
            }
            $lines[] = str_repeat(' ', 4 * (substr_count($prefix, '.') + 1)).var_export($key, true).' => '.$export.',';
        }

        return "[\n".implode("\n", $lines)."\n".str_repeat(' ', 4 * substr_count($prefix, '.')).']';
    }

    /**
     * Failed filesystem operations are reported by the atomic replacement caller.
     *
     * @SuppressWarnings("PHPMD.ErrorControlOperator")
     */
    private function writeEnvironment(Filesystem $files, string $temporary, string $path, string $contents, int $mode): bool
    {
        return @chmod($temporary, $mode & 0777)
            && @$files->put($temporary, $contents) === strlen($contents)
            && @$files->move($temporary, $path);
    }
}
