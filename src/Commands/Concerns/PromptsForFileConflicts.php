<?php

declare(strict_types=1);

namespace MohamedAshrafElsaed\ClaudeKit\Commands\Concerns;

use Closure;
use Illuminate\Support\Str;

use function Laravel\Prompts\select;

use MohamedAshrafElsaed\ClaudeKit\Support\FileWriter;

/**
 * Asks what to do with a file that already exists and differs from the version
 * claude-kit would write. Shared by `claude-kit:install` and `claude-kit:update`,
 * which offer the same choices; update simply reaches this point for fewer files,
 * because FileWriter refreshes anything the project has not edited on its own.
 */
trait PromptsForFileConflicts
{
    /**
     * Returns null when there is nobody to ask, which leaves existing files alone.
     *
     * @return (Closure(string, string, string): string)|null
     */
    private function fileResolver(): ?Closure
    {
        if (! $this->input->isInteractive()) {
            return null;
        }

        $applyToAll = null;

        return function (string $path, string $existing, string $incoming) use (&$applyToAll): string {
            if (is_string($applyToAll)) {
                return $applyToAll;
            }

            while (true) {
                $choice = (string) select(
                    label: "{$path} exists and differs from the claude-kit version.",
                    options: [
                        'skip' => 'Keep mine',
                        'overwrite' => 'Replace it',
                        'diff' => 'Show me the difference first',
                        'skip-all' => 'Keep mine — and for every remaining file',
                        'overwrite-all' => 'Replace it — and every remaining file',
                    ],
                    default: 'skip',
                );

                if ($choice === 'diff') {
                    $this->line(FileWriter::diff($existing, $incoming));

                    continue;
                }

                if ($choice === 'skip-all' || $choice === 'overwrite-all') {
                    $applyToAll = Str::before($choice, '-all');

                    return $applyToAll;
                }

                return $choice;
            }
        };
    }
}
