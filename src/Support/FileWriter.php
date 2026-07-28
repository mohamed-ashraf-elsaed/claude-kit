<?php

declare(strict_types=1);

namespace MohamedAshrafElsaed\ClaudeKit\Support;

use Closure;
use Illuminate\Filesystem\Filesystem;

/**
 * Every write the installer makes goes through here, so one place decides what
 * happens to a file that already exists and one place records what was done.
 *
 *   - target missing            → written, reported `created`
 *   - target byte-identical     → nothing written, reported `unchanged`
 *   - target differs, `--force` → written, reported `overwritten`
 *   - target differs but still  → written, reported `updated`; the project has not
 *     matches the hash we         touched it since claude-kit published it, so
 *     published                   there is no edit to lose
 *   - target differs, resolver  → the resolver decides (`overwrite` or anything
 *                                 else meaning skip); with no resolver the file
 *                                 is left alone and reported `skipped`
 *
 * The resolver is supplied by the command layer, which is where prompting
 * belongs — this class and the Installer stay pure filesystem work.
 */
final class FileWriter
{
    /**
     * @var list<array{action: string, path: string}>
     */
    private array $report = [];

    /**
     * @var array<string, string>
     */
    private array $hashes = [];

    /**
     * @param  (Closure(string, string, string): string)|null  $resolver  ($relativePath, $existing, $incoming): 'overwrite'|'skip'
     * @param  array<string, string>  $publishedHashes  path => sha256 recorded when claude-kit last published it
     */
    public function __construct(
        private readonly Filesystem $files,
        private readonly string $basePath,
        private readonly bool $force,
        private readonly ?Closure $resolver = null,
        private readonly array $publishedHashes = [],
    ) {}

    /**
     * Publish a file the project owns: an existing copy is only replaced with
     * `--force` or an explicit decision from the resolver.
     */
    public function put(string $relative, string $contents): void
    {
        $target = $this->basePath.'/'.$relative;

        if (! $this->files->exists($target)) {
            $this->write($relative, $contents, 'created');

            return;
        }

        $existing = $this->files->get($target);

        if ($existing === $contents) {
            $this->hashes[$relative] = hash('sha256', $contents);
            $this->record('unchanged', $relative);

            return;
        }

        if ($this->force) {
            $this->write($relative, $contents, 'overwritten');

            return;
        }

        if (($this->publishedHashes[$relative] ?? null) === hash('sha256', $existing)) {
            $this->write($relative, $contents, 'updated');

            return;
        }

        if ($this->resolver instanceof Closure && ($this->resolver)($relative, $existing, $contents) === 'overwrite') {
            $this->write($relative, $contents, 'overwritten');

            return;
        }

        $this->record('skipped', $relative);
    }

    /**
     * Write a file whose contents already incorporate whatever the project had
     * (a merged settings.json, a CLAUDE.md managed block). Nothing is lost, so
     * there is nothing to ask about.
     */
    public function putMerged(string $relative, string $contents): void
    {
        $target = $this->basePath.'/'.$relative;

        if ($this->files->exists($target) && $this->files->get($target) === $contents) {
            $this->hashes[$relative] = hash('sha256', $contents);
            $this->record('unchanged', $relative);

            return;
        }

        $this->write($relative, $contents, $this->files->exists($target) ? 'merged' : 'created');
    }

    public function chmod(string $relative, int $mode): void
    {
        $target = $this->basePath.'/'.$relative;

        if ($this->files->exists($target)) {
            $this->files->chmod($target, $mode);
        }
    }

    public function record(string $action, string $path): void
    {
        $this->report[] = ['action' => $action, 'path' => $path];
    }

    /**
     * @return list<array{action: string, path: string}>
     */
    public function report(): array
    {
        return $this->report;
    }

    /**
     * sha256 of every file this run actually published, for the manifest.
     *
     * @return array<string, string>
     */
    public function hashes(): array
    {
        return $this->hashes;
    }

    /**
     * A line diff of two versions of a file: `-` what is there now, `+` what
     * would replace it. Longest-common-subsequence, no dependencies.
     */
    public static function diff(string $existing, string $incoming): string
    {
        $before = self::lines($existing);
        $after = self::lines($incoming);
        $rows = count($before);
        $columns = count($after);

        $common = array_fill(0, $rows + 1, array_fill(0, $columns + 1, 0));

        for ($row = $rows - 1; $row >= 0; $row--) {
            for ($column = $columns - 1; $column >= 0; $column--) {
                $common[$row][$column] = $before[$row] === $after[$column]
                    ? $common[$row + 1][$column + 1] + 1
                    : max($common[$row + 1][$column], $common[$row][$column + 1]);
            }
        }

        $out = [];
        $row = 0;
        $column = 0;

        while ($row < $rows && $column < $columns) {
            if ($before[$row] === $after[$column]) {
                $out[] = '  '.$before[$row];
                $row++;
                $column++;
            } elseif ($common[$row + 1][$column] >= $common[$row][$column + 1]) {
                $out[] = '- '.$before[$row];
                $row++;
            } else {
                $out[] = '+ '.$after[$column];
                $column++;
            }
        }

        while ($row < $rows) {
            $out[] = '- '.$before[$row];
            $row++;
        }

        while ($column < $columns) {
            $out[] = '+ '.$after[$column];
            $column++;
        }

        return implode("\n", $out);
    }

    private function write(string $relative, string $contents, string $action): void
    {
        $target = $this->basePath.'/'.$relative;

        $this->files->ensureDirectoryExists(dirname($target));
        $this->files->put($target, $contents);

        $this->hashes[$relative] = hash('sha256', $contents);
        $this->record($action, $relative);
    }

    /**
     * @return list<string>
     */
    private static function lines(string $text): array
    {
        $trimmed = rtrim($text, "\r\n");

        if ($trimmed === '') {
            return [];
        }

        $lines = preg_split("/\r\n|\n|\r/", $trimmed);

        return $lines === false ? [] : $lines;
    }
}
