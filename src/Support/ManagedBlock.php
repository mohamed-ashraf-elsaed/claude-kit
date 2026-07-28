<?php

declare(strict_types=1);

namespace MohamedAshrafElsaed\ClaudeKit\Support;

/**
 * A begin/end delimited region claude-kit owns inside a file the project also
 * owns — used for CLAUDE.md, so re-running the installer refreshes the generated
 * rules without touching anything the team wrote around them.
 *
 * Claude Code strips block-level HTML comments from CLAUDE.md before loading it,
 * so the markers cost no context.
 */
final class ManagedBlock
{
    public const BEGIN = '<!-- claude-kit:begin -->';

    public const END = '<!-- claude-kit:end -->';

    public static function wrap(string $body): string
    {
        return self::BEGIN."\n".trim($body, "\n")."\n".self::END."\n";
    }

    /**
     * Replaces the managed block in $existing, or appends one when the file has
     * no markers yet. Never removes content outside the markers.
     *
     * When the file has content of its own, the block's `# Title` is dropped:
     * that file already has a top-level heading, and the block's own sections
     * are `##`, so they belong under it rather than beside a second `#`.
     */
    public static function applyTo(string $existing, string $body): string
    {
        $start = strpos($existing, self::BEGIN);
        $end = strpos($existing, self::END);

        if ($start === false || $end === false || $end < $start) {
            return trim($existing) === ''
                ? self::wrap($body)
                : rtrim($existing, "\n")."\n\n".self::wrap(self::withoutTitle($body));
        }

        $before = substr($existing, 0, $start);
        $after = substr($existing, $end + strlen(self::END));

        if (trim($before.$after) !== '') {
            $body = self::withoutTitle($body);
        }

        return $before.rtrim(self::wrap($body), "\n").$after;
    }

    /**
     * The body without its leading level-one heading.
     */
    private static function withoutTitle(string $body): string
    {
        $trimmed = ltrim($body, "\n");

        if (! str_starts_with($trimmed, '# ')) {
            return $body;
        }

        $break = strpos($trimmed, "\n");

        return $break === false ? '' : ltrim(substr($trimmed, $break + 1), "\n");
    }
}
