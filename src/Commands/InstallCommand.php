<?php

declare(strict_types=1);

namespace MohamedAshrafElsaed\ClaudeKit\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

use MohamedAshrafElsaed\ClaudeKit\Support\FrontendStack;
use MohamedAshrafElsaed\ClaudeKit\Support\Installer;
use MohamedAshrafElsaed\ClaudeKit\Support\InstallOptions;
use MohamedAshrafElsaed\ClaudeKit\Support\SkillInstaller;
use MohamedAshrafElsaed\ClaudeKit\Support\StackDetector;
use MohamedAshrafElsaed\ClaudeKit\Support\TestTool;

final class InstallCommand extends Command
{
    protected $signature = 'claude-kit:install
        {--stack= : inertia-vue|inertia-react|blade|none (auto-detected when omitted)}
        {--force : Overwrite files that already exist}';

    protected $description = 'Interactively configure Claude Code + the quality gate for this Laravel project.';

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

        $force = (bool) $this->option('force');

        $options = $this->input->isInteractive()
            ? $this->promptOptions($files, $stubsPath, $stack, $force)
            : InstallOptions::defaults($stack, $force);

        $this->components->info("Installing claude-kit for a {$stack->label()} project.");

        $report = (new Installer($files, $basePath, $stubsPath))->run(
            $options,
            $this->resolveProjectName($files, $basePath),
        );

        $this->renderReport($report);

        if ($this->input->isInteractive() && confirm('Search skills.sh for additional skills to install?', default: false)) {
            $this->findAndAddSkills($basePath);
        }

        if ($options->hasHook('pre-commit')) {
            $this->configureGitHook($basePath);
        }

        $this->printNextSteps($options);

        return self::SUCCESS;
    }

    private function promptOptions(Filesystem $files, string $stubsPath, FrontendStack $stack, bool $force): InstallOptions
    {
        $pint = confirm('Use Laravel Pint for code style?', default: true);

        $phpstan = confirm('Use PHPStan (Larastan) for static analysis?', default: true);
        $level = 7;
        $strict = true;

        if ($phpstan) {
            $level = (int) select(
                label: 'Which PHPStan level?',
                options: array_map('strval', range(0, 9)),
                default: '7',
                hint: 'Higher is stricter. 7 is a strong default; 9 is max.',
            );
            $strict = confirm('Enable phpstan-strict-rules?', default: true);
        }

        $tests = confirm('Set up a test gate?', default: true);
        $tool = TestTool::Pest;
        $coverage = 80;
        $arch = false;

        if ($tests) {
            $tool = TestTool::from(select(
                label: 'Which test runner?',
                options: [TestTool::Pest->value => 'Pest', TestTool::PHPUnit->value => 'PHPUnit'],
                default: TestTool::Pest->value,
            ));
            $coverageInput = text(
                label: 'Minimum coverage % to enforce (blank = do not enforce)',
                default: '80',
                validate: fn (string $value): ?string => ($value === '' || ctype_digit($value)) ? null : 'Enter a number or leave blank.',
            );
            $coverage = $coverageInput === '' ? null : (int) $coverageInput;

            if ($tool->supportsArchitectureTests()) {
                $arch = confirm('Add the architecture test suite (tests/Arch)?', default: true);
            }
        }

        $hooks = multiselect(
            label: 'Which hooks should enforce the gate?',
            options: [
                'stop' => 'Claude Code Stop hook (runs the gate on every turn)',
                'pre-commit' => 'Git pre-commit hook',
                'feature-docs' => 'Feature-doc requirement (part of the Stop hook)',
            ],
            default: ['stop', 'pre-commit', 'feature-docs'],
        );

        $scaffolding = multiselect(
            label: 'What else should I scaffold?',
            options: [
                'rules' => 'CLAUDE.md engineering rules',
                'docs' => 'Feature-doc templates (features/)',
                'editorconfig' => '.editorconfig + .gitattributes',
                'mcp' => 'Laravel Boost MCP (.mcp.json)',
                'ci' => 'GitHub Actions workflows',
            ],
            default: ['rules', 'docs', 'editorconfig', 'mcp', 'ci'],
        );

        $skills = $this->chooseSkills($files, $stubsPath, $stack);

        return new InstallOptions(
            stack: $stack,
            pint: $pint,
            phpstan: $phpstan,
            phpstanLevel: $level,
            phpstanStrict: $strict,
            tests: $tests,
            testTool: $tool,
            coverageMin: $coverage,
            archTests: $arch,
            hooks: array_map(strval(...), array_values($hooks)),
            skills: $skills,
            scaffolding: array_map(strval(...), array_values($scaffolding)),
            force: $force,
        );
    }

    /**
     * @return list<string>
     */
    private function chooseSkills(Filesystem $files, string $stubsPath, FrontendStack $stack): array
    {
        $bundled = array_map(
            static fn (string $dir): string => basename($dir),
            $files->directories($stubsPath.'/claude/skills'),
        );
        sort($bundled);

        /** @var list<string> $selected */
        $selected = multiselect(
            label: 'Which bundled skills should I install?',
            options: array_combine($bundled, $bundled),
            default: array_values(array_intersect($stack->skills(), $bundled)),
            hint: 'You can search skills.sh for more in the next step.',
        );

        return $selected;
    }

    private function findAndAddSkills(string $basePath): void
    {
        $skills = new SkillInstaller($basePath);

        if (! $skills->isAvailable()) {
            $this->components->warn('`npx` was not found — skipping skills.sh. Install Node.js to use it.');

            return;
        }

        do {
            $query = text('Search skills.sh for (blank to stop)');

            if ($query === '') {
                break;
            }

            $this->line($skills->find($query));

            $package = text('Package name or GitHub URL to install (blank to skip)');

            if ($package !== '') {
                $skills->add($package)
                    ? $this->components->info("Installed skill: {$package}")
                    : $this->components->error("Failed to install: {$package}");
            }
        } while (confirm('Search for another skill?', default: false));
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

    private function printNextSteps(InstallOptions $options): void
    {
        $steps = ['Run `composer install` to install the dev tooling'.($options->hasHook('pre-commit') ? ' and wire the git pre-commit hook.' : '.')];

        if ($options->stack->hasFrontendTooling()) {
            $steps[] = 'Run `npm install` to pull the frontend devDependencies.';
        }

        if ($options->wants('rules')) {
            $steps[] = 'Fill in the TODO placeholders in CLAUDE.md.';
        }

        if ($options->tests && $options->coverageMin !== null) {
            $steps[] = 'Install a coverage driver (`pcov` or Xdebug) to enforce the '.$options->coverageMin.'% gate.';
        }

        $this->newLine();
        $this->components->info('claude-kit installed. Next steps:');
        $this->components->bulletList($steps);
    }
}
