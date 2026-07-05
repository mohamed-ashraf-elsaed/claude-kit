<?php

declare(strict_types=1);

namespace MohamedAshrafElsaed\ClaudeKit\Support;

use Illuminate\Filesystem\Filesystem;

/**
 * Merges claude-kit scripts into the host composer.json without clobbering
 * existing entries, and appends commands to post-autoload-dump. Idempotent.
 */
final class ComposerJsonMerger
{
    public function __construct(private readonly Filesystem $files) {}

    /**
     * @param  array<string, list<string>>  $scripts
     * @param  list<string>  $postAutoloadDump
     */
    public function merge(string $path, array $scripts, array $postAutoloadDump): void
    {
        if (! $this->files->exists($path)) {
            return;
        }

        $json = json_decode($this->files->get($path), true);

        if (! is_array($json)) {
            return;
        }

        $existingScripts = is_array($json['scripts'] ?? null) ? $json['scripts'] : [];

        foreach ($scripts as $name => $command) {
            if (! array_key_exists($name, $existingScripts)) {
                $existingScripts[$name] = $command;
            }
        }

        $hook = $existingScripts['post-autoload-dump'] ?? [];
        $hook = is_array($hook) ? $hook : [$hook];

        foreach ($postAutoloadDump as $command) {
            if (! in_array($command, $hook, true)) {
                $hook[] = $command;
            }
        }

        $existingScripts['post-autoload-dump'] = array_values($hook);
        $json['scripts'] = $existingScripts;

        $this->files->put(
            $path,
            json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE).PHP_EOL,
        );
    }
}
