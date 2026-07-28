<?php

declare(strict_types=1);

namespace MohamedAshrafElsaed\ClaudeKit\Support;

use Illuminate\Filesystem\Filesystem;

/**
 * Reads a stub and substitutes its `{{PLACEHOLDER}}` tokens. Deliberately not a
 * templating engine — plain string replacement is all the stubs need.
 */
final class StubRenderer
{
    public function __construct(
        private readonly Filesystem $files,
        private readonly string $stubsPath,
    ) {}

    public function path(string $relative): string
    {
        return $this->stubsPath.'/'.$relative;
    }

    public function exists(string $relative): bool
    {
        return $this->files->exists($this->path($relative));
    }

    /**
     * @param  array<string, string>  $replacements
     */
    public function render(string $relative, array $replacements = []): string
    {
        $contents = $this->files->get($this->path($relative));

        return $replacements === [] ? $contents : strtr($contents, $replacements);
    }
}
