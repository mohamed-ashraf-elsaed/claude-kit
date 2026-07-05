<?php

declare(strict_types=1);

namespace MohamedAshrafElsaed\ClaudeKit\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;

use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\select;

use MohamedAshrafElsaed\ClaudeKit\Support\FrontendStack;
use MohamedAshrafElsaed\ClaudeKit\Support\Installer;
use MohamedAshrafElsaed\ClaudeKit\Support\Part;
use MohamedAshrafElsaed\ClaudeKit\Support\StackDetector;

final class InstallCommand extends Command
{
    protected $signature = 'claude-kit:install
        {--stack= : inertia-vue|inertia-react|blade|none (auto-detected when omitted)}
        {--parts= : Comma list of parts to install: claude,rules,quality,frontend,docs,ci (default: all)}
        {--force : Overwrite files that already exist}';

    protected $description = 'Scaffold Claude Code rules, hooks, skills, and the quality gate into this Laravel project.';

    public function handle(Filesystem $files): int
    {
        $basePath = $this->laravel->basePath();
        $stubsPath = dirname(__DIR__, 2).'/stubs';

        $stack = $this->resolveStack($files, $basePath);

        if ($stack === null) {
            $this->components->error('Unknown --stack value. Use one of: '.implode(', ', array_map(
                static fn (FrontendStack $s): string => $s->value,
                FrontendStack::cases(),
            )));

            return self::FAILURE;
        }

        $parts = $this->resolveParts();
        $force = (bool) $this->option('force');

        $this->components->info("Installing claude-kit for a {$stack->label()} project.");

        $report = (new Installer($files, $basePath, $stubsPath))->run(
            $parts,
            $stack,
            $force,
            $this->resolveProjectName($files, $basePath),
        );

        $this->renderReport($report);

        if (in_array(Part::Quality->value, $parts, true)) {
            $this->configureGitHook($basePath);
        }

        $this->printNextSteps($stack, $parts);

        return self::SUCCESS;
    }

    private function resolveStack(Filesystem $files, string $basePath): ?FrontendStack
    {
        $option = $this->option('stack');

        if (is_string($option) && $option !== '') {
            return FrontendStack::tryFrom($option);
        }

        $detected = (new StackDetector($files, $basePath))->detect();

        if (! $this->input->isInteractive()) {
            return $detected;
        }

        return FrontendStack::from(select(
            label: 'Which frontend stack is this project using?',
            options: array_reduce(
                FrontendStack::cases(),
                static function (array $carry, FrontendStack $stack): array {
                    $carry[$stack->value] = $stack->label();

                    return $carry;
                },
                [],
            ),
            default: $detected->value,
        ));
    }

    /**
     * @return list<string>
     */
    private function resolveParts(): array
    {
        $all = Part::values();
        $option = $this->option('parts');

        if (is_string($option) && $option !== '') {
            $requested = array_map('trim', explode(',', $option));

            return array_values(array_intersect($all, $requested));
        }

        if (! $this->input->isInteractive()) {
            return $all;
        }

        /** @var list<string> $selected */
        $selected = multiselect(
            label: 'Which parts should claude-kit install?',
            options: array_reduce(
                Part::cases(),
                static function (array $carry, Part $part): array {
                    $carry[$part->value] = $part->label();

                    return $carry;
                },
                [],
            ),
            default: $all,
        );

        return $selected;
    }

    private function resolveProjectName(Filesystem $files, string $basePath): string
    {
        $appName = (string) config('app.name', '');

        if ($appName !== '' && $appName !== 'Laravel') {
            return $appName;
        }

        $composerPath = $basePath.'/composer.json';

        if ($files->exists($composerPath)) {
            $composer = json_decode($files->get($composerPath), true);

            if (is_array($composer) && is_string($composer['name'] ?? null) && str_contains($composer['name'], '/')) {
                return Str::studly(Str::after($composer['name'], '/'));
            }
        }

        return Str::studly(basename($basePath));
    }

    /**
     * @param  list<array{action: string, path: string}>  $report
     */
    private function renderReport(array $report): void
    {
        foreach ($report as $entry) {
            $this->components->twoColumnDetail($entry['path'], $entry['action']);
        }
    }

    private function configureGitHook(string $basePath): void
    {
        if (! is_dir($basePath.'/.git')) {
            return;
        }

        Process::path($basePath)->run('git config core.hooksPath .githooks');
    }

    /**
     * @param  list<string>  $parts
     */
    private function printNextSteps(FrontendStack $stack, array $parts): void
    {
        $steps = ['Run `composer install` to install the dev tooling and wire the git pre-commit hook.'];

        if ($stack->hasFrontendTooling() && in_array(Part::Frontend->value, $parts, true)) {
            $steps[] = 'Run `npm install` to pull the frontend devDependencies.';
        }

        if (in_array(Part::Rules->value, $parts, true)) {
            $steps[] = 'Fill in the TODO placeholders in CLAUDE.md (product context, integrations, deployment).';
        }

        $steps[] = 'Install a coverage driver (`pcov` or Xdebug) so the 80% gate is enforced.';

        $this->newLine();
        $this->components->info('claude-kit installed. Next steps:');

        foreach ($steps as $step) {
            $this->components->bulletList([$step]);
        }
    }
}
