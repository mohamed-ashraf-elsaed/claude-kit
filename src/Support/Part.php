<?php

declare(strict_types=1);

namespace MohamedAshrafElsaed\ClaudeKit\Support;

/**
 * The installable parts of the kit. Users may pick a subset; by default all are
 * installed.
 */
enum Part: string
{
    case Claude = 'claude';
    case Rules = 'rules';
    case Quality = 'quality';
    case Frontend = 'frontend';
    case Docs = 'docs';
    case Ci = 'ci';

    public function label(): string
    {
        return match ($this) {
            self::Claude => '.claude/ core (settings, Stop hook, skills, MCP)',
            self::Rules => 'CLAUDE.md engineering rules',
            self::Quality => 'Quality gate (PHPStan, Pint, arch tests, pre-commit hook)',
            self::Frontend => 'Frontend tooling (ESLint, Prettier, type-check)',
            self::Docs => 'Feature-doc templates, .editorconfig, .gitattributes',
            self::Ci => 'GitHub Actions workflows (tests, lint)',
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $part): string => $part->value, self::cases());
    }
}
