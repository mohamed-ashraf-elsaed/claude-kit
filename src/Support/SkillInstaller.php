<?php

declare(strict_types=1);

namespace MohamedAshrafElsaed\ClaudeKit\Support;

use Illuminate\Support\Facades\Process;

/**
 * Thin wrapper over the skills.sh CLI (`npx skills`) used to discover and
 * install additional Claude Code skills that claude-kit does not bundle.
 *
 * @see https://www.skills.sh/vercel-labs/skills/find-skills
 */
final class SkillInstaller
{
    public function __construct(private readonly string $basePath) {}

    /**
     * Whether `npx` is available on the host, gating the skills.sh features.
     */
    public function isAvailable(): bool
    {
        return Process::path($this->basePath)->run('npx --version')->successful();
    }

    /**
     * Search the skills.sh registry for skills matching a query.
     * Returns the CLI's raw output for display.
     */
    public function find(string $query): string
    {
        $result = Process::path($this->basePath)
            ->timeout(120)
            ->run(['npx', '--yes', 'skills', 'find', $query]);

        return trim($result->output().$result->errorOutput());
    }

    /**
     * Install a skill by package reference (a name or a GitHub URL) into the
     * project's .claude/skills directory. Returns true on success.
     */
    public function add(string $package): bool
    {
        return Process::path($this->basePath)
            ->timeout(300)
            ->run(['npx', '--yes', 'skills', 'add', $package])
            ->successful();
    }
}
