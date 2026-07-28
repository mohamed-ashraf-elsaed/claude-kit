<?php

declare(strict_types=1);

namespace MohamedAshrafElsaed\ClaudeKit\Support;

use Illuminate\Filesystem\Filesystem;

/**
 * Reads and writes `.claude/claude-kit.json`: the record of what the installer
 * selected and the hash of every file it published. `claude-kit:update` reads it
 * to know what to re-publish and to tell an untouched file from an edited one.
 *
 * Reading falls back to the legacy root `.claude-kit.json` so projects installed
 * before the manifest moved keep working.
 */
final class Manifest
{
    public const PATH = '.claude/claude-kit.json';

    public const LEGACY_PATH = '.claude-kit.json';

    public function __construct(
        private readonly Filesystem $files,
        private readonly string $basePath,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function read(): array
    {
        foreach ([self::PATH, self::LEGACY_PATH] as $relative) {
            $path = $this->basePath.'/'.$relative;

            if (! $this->files->exists($path)) {
                continue;
            }

            $decoded = json_decode($this->files->get($path), true);

            if (is_array($decoded)) {
                /** @var array<string, mixed> $decoded */
                return $decoded;
            }
        }

        return [];
    }

    public function exists(): bool
    {
        return $this->files->exists($this->basePath.'/'.self::PATH)
            || $this->files->exists($this->basePath.'/'.self::LEGACY_PATH);
    }

    /**
     * The hash recorded for each file the last install/update published.
     *
     * @return array<string, string>
     */
    public function publishedHashes(): array
    {
        $files = $this->read()['files'] ?? null;

        if (! is_array($files)) {
            return [];
        }

        $hashes = [];

        foreach ($files as $path => $hash) {
            if (is_string($path) && is_string($hash)) {
                $hashes[$path] = $hash;
            }
        }

        return $hashes;
    }

    /**
     * Writes the manifest to the current path. Returns the action taken so the
     * caller can report it alongside the published files.
     *
     * @param  array<string, mixed>  $data
     */
    public function write(array $data): string
    {
        $path = $this->basePath.'/'.self::PATH;
        $existed = $this->files->exists($path);

        $this->files->ensureDirectoryExists(dirname($path));
        $this->files->put(
            $path,
            json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE).PHP_EOL,
        );

        return $existed ? 'overwritten' : 'created';
    }
}
