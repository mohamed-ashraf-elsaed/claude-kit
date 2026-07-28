<?php

declare(strict_types=1);

namespace MohamedAshrafElsaed\ClaudeKit\Support;

/**
 * How strict the published clean-code rule is. Each preset is a distinct variant
 * of the rule stub, not a knob on one file — the thresholds and the wording both
 * change.
 */
enum CleanCodePreset: string
{
    case Strict = 'strict';
    case Balanced = 'balanced';
    case Relaxed = 'relaxed';

    public function label(): string
    {
        return match ($this) {
            self::Strict => 'strict — hard limits, no exceptions',
            self::Balanced => 'balanced — limits with documented escape hatches',
            self::Relaxed => 'relaxed — direction, not thresholds',
        };
    }

    /**
     * The rule stub published as `.claude/rules/clean-code.md`.
     */
    public function stub(): string
    {
        return 'claude/rules/clean-code.'.$this->value.'.md';
    }
}
