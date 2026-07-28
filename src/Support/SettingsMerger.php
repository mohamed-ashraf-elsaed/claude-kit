<?php

declare(strict_types=1);

namespace MohamedAshrafElsaed\ClaudeKit\Support;

/**
 * Deep-merges the settings claude-kit needs into an existing
 * `.claude/settings.json` without ever removing or rewriting what the project
 * already had:
 *
 *   - a key we set that the project does not have is added;
 *   - two maps are merged recursively;
 *   - two lists are unioned (our entries appended if absent), so re-running the
 *     installer never duplicates a hook;
 *   - on a scalar conflict the project's value is kept, and the key is reported
 *     as `kept` so the installer can say what it left alone.
 */
final class SettingsMerger
{
    /**
     * @var list<string>
     */
    private array $added = [];

    /**
     * @var list<string>
     */
    private array $changed = [];

    /**
     * @var list<string>
     */
    private array $kept = [];

    /**
     * @param  array<string, mixed>  $ours
     * @param  array<string, mixed>  $theirs
     * @return array<string, mixed>
     */
    public function merge(array $ours, array $theirs): array
    {
        $this->added = [];
        $this->changed = [];
        $this->kept = [];

        return $this->mergeInto($ours, $theirs, '');
    }

    /**
     * Dotted key paths this merge added, changed, and deliberately left alone.
     *
     * @return array{added: list<string>, changed: list<string>, kept: list<string>}
     */
    public function summary(): array
    {
        return ['added' => $this->added, 'changed' => $this->changed, 'kept' => $this->kept];
    }

    /**
     * @param  array<array-key, mixed>  $ours
     * @param  array<array-key, mixed>  $theirs
     * @return array<array-key, mixed>
     */
    private function mergeInto(array $ours, array $theirs, string $prefix): array
    {
        $merged = $theirs;

        foreach ($ours as $key => $value) {
            $path = $prefix === '' ? (string) $key : $prefix.'.'.$key;

            if (! array_key_exists($key, $theirs)) {
                $merged[$key] = $value;
                $this->added[] = $path;

                continue;
            }

            $existing = $theirs[$key];

            if (is_array($value) && is_array($existing)) {
                $merged[$key] = array_is_list($value) && array_is_list($existing)
                    ? $this->union($value, $existing, $path)
                    : $this->mergeInto($value, $existing, $path);

                continue;
            }

            if ($value !== $existing) {
                $this->kept[] = $path;
            }
        }

        return $merged;
    }

    /**
     * @param  list<mixed>  $ours
     * @param  list<mixed>  $theirs
     * @return list<mixed>
     */
    private function union(array $ours, array $theirs, string $path): array
    {
        $merged = $theirs;

        foreach ($ours as $entry) {
            if (! in_array($entry, $theirs, true)) {
                $merged[] = $entry;
            }
        }

        if (count($merged) !== count($theirs)) {
            $this->changed[] = $path;
        }

        return $merged;
    }
}
