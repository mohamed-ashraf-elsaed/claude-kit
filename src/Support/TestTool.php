<?php

declare(strict_types=1);

namespace MohamedAshrafElsaed\ClaudeKit\Support;

/**
 * The test runner a project uses. Drives the gate's test command and whether
 * Pest-only scaffolding (architecture tests) is applicable.
 */
enum TestTool: string
{
    case Pest = 'pest';
    case PHPUnit = 'phpunit';

    public function label(): string
    {
        return match ($this) {
            self::Pest => 'Pest',
            self::PHPUnit => 'PHPUnit',
        };
    }

    public function binary(): string
    {
        return match ($this) {
            self::Pest => 'vendor/bin/pest',
            self::PHPUnit => 'vendor/bin/phpunit',
        };
    }

    public function supportsArchitectureTests(): bool
    {
        return $this === self::Pest;
    }
}
