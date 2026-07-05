<?php

declare(strict_types=1);

namespace MohamedAshrafElsaed\ClaudeKit\Support;

use Illuminate\Filesystem\Filesystem;

/**
 * Merges claude-kit scripts and devDependencies into the host package.json
 * without clobbering existing entries. Idempotent: re-running adds nothing new.
 */
final class PackageJsonMerger
{
    public function __construct(private readonly Filesystem $files) {}

    /**
     * @param  array<string, string>  $scripts
     * @param  array<string, string>  $devDependencies
     */
    public function merge(string $path, array $scripts, array $devDependencies): void
    {
        $json = $this->read($path);

        $json['scripts'] = $this->preserveExisting($scripts, $json['scripts'] ?? []);
        $json['devDependencies'] = $this->preserveExisting($devDependencies, $json['devDependencies'] ?? []);
        ksort($json['devDependencies']);

        $this->files->put($path, $this->encode($json));
    }

    /**
     * @param  array<string, string>  $ours
     * @param  array<string, mixed>  $existing
     * @return array<string, mixed>
     */
    private function preserveExisting(array $ours, array $existing): array
    {
        return array_merge($ours, $existing);
    }

    /**
     * @return array<string, mixed>
     */
    private function read(string $path): array
    {
        if (! $this->files->exists($path)) {
            return ['private' => true, 'type' => 'module'];
        }

        $decoded = json_decode($this->files->get($path), true);

        return is_array($decoded) ? $decoded : ['private' => true, 'type' => 'module'];
    }

    /**
     * @param  array<string, mixed>  $json
     */
    private function encode(array $json): string
    {
        return json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE).PHP_EOL;
    }
}
