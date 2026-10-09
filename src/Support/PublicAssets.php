<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\Support;

use Illuminate\Filesystem\Filesystem;
use JsonException;
use RuntimeException;

class PublicAssets
{
    public function __construct(private Filesystem $files)
    {
    }

    public function publish(): array
    {
        $destination = public_path('vendor/laravelusers');
        $this->assertDirectory(public_path('vendor'));
        $this->assertDirectory($destination);
        $this->assertDirectory($destination.'/releases');
        $lockPath = storage_path('app/laravelusers/assets.lock');
        $this->files->ensureDirectoryExists(dirname($lockPath));
        if (is_link($lockPath)) {
            throw new RuntimeException('The asset publication lock cannot be a symbolic link.');
        }
        $lock = fopen($lockPath, 'c');
        if ($lock === false) {
            throw new RuntimeException('Unable to open the asset publication lock.');
        }

        try {
            if (!flock($lock, LOCK_EX)) {
                throw new RuntimeException('Unable to acquire the asset publication lock.');
            }

            return $this->publishRelease($destination);
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    private function publishRelease(string $destination): array
    {
        [$contents, $hashes] = $this->sourceFiles();
        $release = hash('sha256', json_encode($hashes, JSON_THROW_ON_ERROR));
        $releasePath = $destination.'/releases/'.$release;
        $stage = $destination.'/.stage-'.bin2hex(random_bytes(12));
        $this->assertDirectory($stage);

        try {
            $this->stageFiles($stage, $contents, $hashes);
            $this->activateRelease($stage, $releasePath, $contents);
            $this->writeManifest($destination, $release, $hashes);

            return array_keys($hashes);
        } finally {
            $this->files->deleteDirectory($stage);
        }
    }

    private function sourceFiles(): array
    {
        $contents = [];
        $hashes = [];
        foreach ($this->files->files(self::sourceDirectory()) as $file) {
            $name = $file->getFilename();
            if (!preg_match('/\A(?:[a-z0-9-]+\.(?:css|js)|runtime-(?:livewire|vue|react|svelte)\.licenses\.json)\z/', $name) || $file->isLink()) {
                throw new RuntimeException('The package contains an unexpected public asset.');
            }
            $contents[$name] = $this->files->get($file->getPathname());
            $hashes[$name] = hash('sha256', $contents[$name]);
        }
        if ($contents === []) {
            throw new RuntimeException('No package assets were found.');
        }
        ksort($hashes);

        return [$contents, $hashes];
    }

    private function stageFiles(string $stage, array $contents, array $hashes): void
    {
        foreach ($contents as $name => $content) {
            $this->files->replace($stage.'/'.$name, $content, 0644);
            if (hash_file('sha256', $stage.'/'.$name) !== $hashes[$name]) {
                throw new RuntimeException('Unable to verify a staged package asset.');
            }
        }
    }

    private function activateRelease(string $stage, string $releasePath, array $contents): void
    {
        if (!$this->files->isDirectory($releasePath)) {
            if (!$this->files->moveDirectory($stage, $releasePath)) {
                throw new RuntimeException('Unable to activate the staged asset directory.');
            }

            return;
        }
        $this->assertDirectory($releasePath);
        foreach ($contents as $name => $content) {
            if (is_link($releasePath.'/'.$name)) {
                throw new RuntimeException('Published package assets cannot be symbolic links.');
            }
            $this->files->replace($releasePath.'/'.$name, $content, 0644);
        }
    }

    private function writeManifest(string $destination, string $release, array $hashes): void
    {
        $manifest = ['release' => $release, 'files' => []];
        foreach ($hashes as $name => $hash) {
            $manifest['files'][$name] = ['path' => 'releases/'.$release.'/'.$name, 'sha256' => $hash];
        }
        if (is_link($destination.'/manifest.json')) {
            throw new RuntimeException('The asset manifest cannot be a symbolic link.');
        }
        $this->files->replace($destination.'/manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n", 0644);
    }

    private function assertDirectory(string $path): void
    {
        if (is_link($path) || (file_exists($path) && !is_dir($path))) {
            throw new RuntimeException('The package asset destination must be a directory, not a file or symbolic link.');
        }
        $this->files->ensureDirectoryExists($path);
    }

    public static function url(string $name): ?string
    {
        $source = self::source($name);
        $manifest = self::manifest();
        $release = $manifest['release'] ?? null;
        $entry = $manifest['files'][$name] ?? null;
        if (!is_string($release) || !preg_match('/\A[a-f0-9]{64}\z/', $release) || !is_array($entry)) {
            return null;
        }
        $path = 'releases/'.$release.'/'.$name;
        if (!self::verifiedAsset($path, $entry, $source)) {
            return null;
        }

        return asset('vendor/laravelusers/'.$path);
    }

    private static function manifest(): ?array
    {
        $manifestPath = public_path('vendor/laravelusers/manifest.json');
        if (!is_file($manifestPath) || is_link($manifestPath)) {
            return null;
        }

        try {
            $manifest = json_decode((string) file_get_contents($manifestPath), true, 8, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }

        return is_array($manifest) ? $manifest : null;
    }

    private static function verifiedAsset(string $path, array $entry, string $source): bool
    {
        $published = public_path('vendor/laravelusers/'.$path);

        return ($entry['path'] ?? null) === $path
            && ($entry['sha256'] ?? null) === hash_file('sha256', $source)
            && is_file($published) && !is_link($published)
            && hash_file('sha256', $published) === $entry['sha256'];
    }

    public static function contents(string $name): string
    {
        return file_get_contents(self::source($name));
    }

    private static function source(string $name): string
    {
        if (!preg_match('/\A(?:[a-z0-9-]+\.(?:css|js)|runtime-(?:livewire|vue|react|svelte)\.licenses\.json)\z/', $name) || !is_file(self::sourceDirectory().'/'.$name)) {
            throw new RuntimeException('Unknown package asset.');
        }

        return self::sourceDirectory().'/'.$name;
    }

    private static function sourceDirectory(): string
    {
        return dirname(__DIR__).'/resources/assets';
    }
}
