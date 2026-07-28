<?php

declare(strict_types=1);

namespace MohamedAshrafElsaed\ClaudeKit\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use MohamedAshrafElsaed\ClaudeKit\Commands\Concerns\PromptsForFileConflicts;
use MohamedAshrafElsaed\ClaudeKit\Support\FrontendStack;
use MohamedAshrafElsaed\ClaudeKit\Support\Installer;
use MohamedAshrafElsaed\ClaudeKit\Support\InstallOptions;
use MohamedAshrafElsaed\ClaudeKit\Support\Manifest;

final class UpdateCommand extends Command
{
    use PromptsForFileConflicts;

    protected $signature = 'claude-kit:update
        {--force : Replace every file, including ones you have edited}';

    protected $description = 'Re-publish the stubs for the options recorded at install time, keeping your edits.';

    public function handle(Filesystem $files): int
    {
        $basePath = $this->laravel->basePath();
        $manifest = new Manifest($files, $basePath);

        if (! $manifest->exists()) {
            $this->components->error(
                'No '.Manifest::PATH.' in this project — run `php artisan claude-kit:install` first.'
            );

            return self::FAILURE;
        }

        $recorded = $manifest->read();
        $stack = $this->resolveStack($recorded);

        if ($stack === null) {
            $this->components->error(
                'The manifest records an unknown frontend stack. Fix "stack" in '.Manifest::PATH
                .' or re-run `php artisan claude-kit:install`.'
            );

            return self::FAILURE;
        }

        $options = InstallOptions::fromManifest($recorded, $stack, (bool) $this->option('force'));

        $this->components->info("Updating claude-kit for a {$stack->label()} project.");

        $installer = new Installer(
            $files,
            $basePath,
            dirname(__DIR__, 2).'/stubs',
            $this->fileResolver(),
            fn (string $message): null => $this->line($message),
            // Anything still matching the hash we recorded has not been edited, so
            // it is refreshed silently; only genuine edits reach the resolver.
            $manifest->publishedHashes(),
        );

        $report = $installer->run($options, $this->projectName($recorded, $basePath));

        $this->renderReport($report);
        $this->renderKeptSettings($installer->settingsSummary()['kept']);

        return self::SUCCESS;
    }

    /**
     * @param  array<string, mixed>  $recorded
     */
    private function resolveStack(array $recorded): ?FrontendStack
    {
        $stack = $recorded['stack'] ?? null;

        return is_string($stack) ? FrontendStack::tryFrom($stack) : null;
    }

    /**
     * @param  array<string, mixed>  $recorded
     */
    private function projectName(array $recorded, string $basePath): string
    {
        $name = $recorded['project_name'] ?? null;

        return is_string($name) && $name !== '' ? $name : Str::studly(basename($basePath));
    }

    /**
     * @param  list<array{action: string, path: string}>  $report
     */
    private function renderReport(array $report): void
    {
        $changed = array_values(array_filter(
            $report,
            static fn (array $entry): bool => $entry['action'] !== 'unchanged',
        ));

        foreach ($changed as $entry) {
            $this->components->twoColumnDetail($entry['path'], $entry['action']);
        }

        $this->newLine();
        $this->components->info(sprintf(
            '%d file(s) changed, %d already current.',
            count($changed),
            count($report) - count($changed),
        ));
    }

    /**
     * @param  list<string>  $kept
     */
    private function renderKeptSettings(array $kept): void
    {
        if ($kept !== []) {
            $this->components->warn(
                'Left your existing .claude/settings.json values alone: '.implode(', ', $kept)
            );
        }
    }
}
