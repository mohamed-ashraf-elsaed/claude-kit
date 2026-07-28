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

use MohamedAshrafElsaed\ClaudeKit\Commands\Concerns\PromptsForFileConflicts;
use MohamedAshrafElsaed\ClaudeKit\Support\CleanCodePreset;
use MohamedAshrafElsaed\ClaudeKit\Support\FrontendStack;
use MohamedAshrafElsaed\ClaudeKit\Support\Installer;
use MohamedAshrafElsaed\ClaudeKit\Support\InstallOptions;
use MohamedAshrafElsaed\ClaudeKit\Support\SkillInstaller;
use MohamedAshrafElsaed\ClaudeKit\Support\StackDetector;
use MohamedAshrafElsaed\ClaudeKit\Support\TestTool;

final class InstallCommand extends Command
{
    use PromptsForFileConflicts;

    protected $signature = 'claude-kit:install
        {--stack= : inertia-vue|inertia-react|livewire|blade|none (auto-detected when omitted)}
        {--force : Overwrite files that already exist without asking}';

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

        $installer = new Installer(
            $files,
            $basePath,
            $stubsPath,
            $this->fileResolver(),
            fn (string $message): null => $this->line($message),
        );

        $report = $installer->run($options, $this->resolveProjectName($files, $basePath));

        $this->renderReport($report);
        $this->renderSettingsSummary($installer->settingsSummary());

        $pendingComposer = $this->installDevTooling($files, $basePath, $options);

        if ($this->input->isInteractive() && confirm('Search skills.sh for additional skills to install?', default: false)) {
            $this->findAndAddSkills($basePath);
        }

        if ($options->hasHook('pre-commit')) {
            $this->configureGitHook($basePath);
        }

        $this->printNextSteps($options, $pendingComposer, $installer->manualSteps());

        return self::SUCCESS;
    }

    private function promptOptions(Filesystem $files, string $stubsPath, FrontendStack $stack, bool $force): InstallOptions
    {
        $this->section('Code style & static analysis');
        $pint = confirm('Use Laravel Pint for code style?', default: true);

        $phpstan = confirm('Use PHPStan for static analysis?', default: true);
        $level = 7;
        $strict = true;
        $larastan = true;
        $phpstanHook = 'off';

        if ($phpstan) {
            $level = (int) select(
                label: 'Which PHPStan level?',
                options: array_map('strval', range(0, 9)),
                default: '7',
                hint: 'Higher is stricter. 7 is a strong default; 9 is max.',
            );
            $larastan = confirm('Add Larastan (Laravel-aware analysis)?', default: true);
            $strict = confirm('Enable phpstan-strict-rules?', default: true);

            if (confirm('Analyse every PHP file Claude edits, as it edits it?', default: true)) {
                $phpstanHook = (string) select(
                    label: 'When that check fails, what should happen?',
                    options: [
                        'block' => 'Block — feed the errors back so Claude fixes them before continuing',
                        'warn' => 'Warn only — show a warning and let Claude carry on',
                    ],
                    default: 'block',
                );
            }
        }

        $this->section('Tests');
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
                placeholder: 'e.g. 80',
                validate: fn (string $value): ?string => ($value === '' || ctype_digit($value)) ? null : 'Enter a number or leave blank.',
            );
            $coverage = $coverageInput === '' ? null : (int) $coverageInput;

            if ($tool->supportsArchitectureTests()) {
                $arch = confirm('Add the architecture test suite (tests/Arch)?', default: true);
            }
        }

        $this->section('Architecture');
        $architecture = array_map(strval(...), array_values(multiselect(
            label: 'Which layers does this project use?',
            options: [
                'services' => 'Services (app/Services) — business logic',
                'repositories' => 'Repositories (app/Repositories) — named persistence queries',
                'data-objects' => 'Data Objects (app/Data) — readonly DTOs',
                'value-objects' => 'Value Objects (app/ValueObjects) — self-validating types',
                'actions' => 'Actions (app/Actions) — one invokable class per operation',
                'contracts' => 'Contracts (app/Contracts) — interfaces for boundaries',
            ],
            default: ['services', 'data-objects', 'actions', 'contracts'],
            hint: 'A layer you skip gets no rule, no arch test, and no mention in CLAUDE.md.',
        )));

        $cleanCode = CleanCodePreset::from(select(
            label: 'How strict should the clean-code rule be?',
            options: array_reduce(
                CleanCodePreset::cases(),
                static function (array $carry, CleanCodePreset $preset): array {
                    $carry[$preset->value] = $preset->label();

                    return $carry;
                },
                [],
            ),
            default: CleanCodePreset::Balanced->value,
        ));

        $this->section('Enforcement hooks');
        $hooks = [];
        $checks = [];
        $minPasses = 2;

        if (confirm('Validate the quality gate when Claude finishes a turn (Stop hook)?', default: true)) {
            $hooks[] = 'stop';
            $checks = array_map(strval(...), array_values(multiselect(
                label: 'Which checks must pass before a turn may finish?',
                options: [
                    'pint' => 'Pint (code style)',
                    'phpstan' => 'PHPStan (static analysis)',
                    'tests' => 'Test suite',
                ],
                default: array_values(array_filter([
                    $pint ? 'pint' : null,
                    $phpstan ? 'phpstan' : null,
                    $tests ? 'tests' : null,
                ])),
            )));
            $minPasses = (int) select(
                label: 'How many consecutive clean runs before the turn is allowed to end?',
                options: array_map('strval', range(1, 5)),
                default: '2',
                hint: 'Each run repeats the selected checks; any failure blocks the turn.',
            );
        }

        if (confirm('Install the git pre-commit hook?', default: true)) {
            $hooks[] = 'pre-commit';
        }

        if (confirm('Require a feature doc for changed code (part of the Stop hook)?', default: true)) {
            $hooks[] = 'feature-docs';
        }

        $this->section('Reviewers, skills & rules');
        $agents = array_map(strval(...), array_values(multiselect(
            label: 'Which read-only review agents should I install?',
            options: [
                'design-reviewer' => 'design-reviewer — checks the result against the stated design/plan',
                'docs-reviewer' => 'docs-reviewer — checks the result against supplied docs',
            ],
            default: InstallOptions::AGENTS,
        )));

        $workflowSkills = array_map(strval(...), array_values(multiselect(
            label: 'Which workflow skills should I install?',
            options: [
                'commit' => '/commit — stage and commit to this project\'s convention',
                'deploy' => '/deploy — run the deploy, including per-feature steps',
                'dead-code' => '/dead-code — detect, prove, then remove unused code',
                'codemap' => '/codemap — create or refresh CODEMAP.md',
            ],
            default: InstallOptions::WORKFLOW_SKILLS,
        )));

        $deployCommand = '';
        $commitConvention = null;

        if (in_array('deploy', $workflowSkills, true)) {
            $deployCommand = text(
                label: 'The exact deploy command (blank leaves a TODO — nothing is invented)',
                placeholder: 'e.g. php artisan deploy --force',
            );
        }

        if (in_array('commit', $workflowSkills, true)) {
            $useConventional = select(
                label: 'Which commit message convention?',
                options: [
                    'conventional' => 'Conventional Commits (feat:, fix:, refactor: …)',
                    'custom' => 'Something else — I will describe it',
                ],
                default: 'conventional',
            );

            if ($useConventional === 'custom') {
                $described = text(
                    label: 'Describe the convention (used verbatim, exactly as typed)',
                    required: true,
                );
                $commitConvention = $described;
            }
        }

        $behavioral = array_map(strval(...), array_values(multiselect(
            label: 'Which always-on behavioural rules should I install?',
            options: [
                'anti-overengineering' => 'Build the smallest thing that satisfies the requirement',
                'anti-assumption' => 'Verify or ask — never invent a detail',
            ],
            default: InstallOptions::BEHAVIORAL_RULES,
        )));

        $worktrees = confirm('Document the git-worktree workflow for parallel features?', default: true);
        $outputStyle = confirm('Install the concise response style (and select it)?', default: true);

        $this->section('Scaffolding');
        $scaffolding = multiselect(
            label: 'What else should I scaffold?',
            options: [
                'rules' => 'CLAUDE.md + .claude/rules/ conventions',
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
            phpstanLarastan: $larastan,
            phpstanHook: $phpstanHook,
            tests: $tests,
            testTool: $tool,
            coverageMin: $coverage,
            archTests: $arch,
            architecture: $architecture,
            cleanCode: $cleanCode,
            hooks: $hooks,
            validationChecks: $checks,
            minPasses: $minPasses,
            agents: $agents,
            workflowSkills: $workflowSkills,
            deployCommand: $deployCommand,
            commitConvention: $commitConvention,
            behavioralRules: $behavioral,
            outputStyle: $outputStyle,
            worktreeRule: $worktrees,
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

    private function section(string $title): void
    {
        $this->newLine();
        $this->line("<fg=cyan;options=bold>── {$title} ──</>");
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

    /**
     * @param  array{added: list<string>, changed: list<string>, kept: list<string>}  $summary
     */
    private function renderSettingsSummary(array $summary): void
    {
        if ($summary['kept'] === []) {
            return;
        }

        $this->components->warn(
            'Left your existing .claude/settings.json values alone: '.implode(', ', $summary['kept'])
        );
    }

    /**
     * claude-kit does not require the tools it configures, so the selected ones
     * have to be added to the project. Returns the command still to be run, or
     * null when there is nothing left to do.
     */
    private function installDevTooling(Filesystem $files, string $basePath, InstallOptions $options): ?string
    {
        $missing = array_values(array_diff(
            $options->requiredDevPackages(),
            $this->installedPackages($files, $basePath),
        ));

        if ($missing === []) {
            return null;
        }

        $command = 'composer require --dev '.implode(' ', $missing);

        if (! $this->input->isInteractive()) {
            return $command;
        }

        $this->newLine();
        $this->components->info('The selected tooling is not installed yet: '.implode(', ', $missing));

        if (! confirm("Run `{$command}` now?", default: true)) {
            return $command;
        }

        $result = Process::path($basePath)->timeout(600)->run($command);

        if ($result->successful()) {
            $this->components->info('Installed: '.implode(', ', $missing));

            return null;
        }

        $output = trim($result->errorOutput().$result->output());

        $this->components->error('composer could not install them. Output:');
        $this->line($output);

        if ($this->isPestPhpunitConflict($output)) {
            $this->explainPestConflict($command);
        }

        return $command;
    }

    /**
     * Composer refused because Pest and PHPUnit cannot both be satisfied — the
     * shape of failure a current Laravel skeleton produces.
     */
    private function isPestPhpunitConflict(string $output): bool
    {
        return str_contains($output, 'pestphp/pest')
            && str_contains($output, 'phpunit/phpunit')
            && str_contains($output, 'conflict');
    }

    private function explainPestConflict(string $command): void
    {
        $this->newLine();
        $this->line('  This is a known conflict upstream of claude-kit, not something the kit can fix:');
        $this->line('  current Pest releases conflict with phpunit/phpunit newer than 12.5.30, and the');
        $this->line('  Laravel 13 skeleton pins a newer PHPUnit than that, so a plain require cannot');
        $this->line('  resolve. Re-running with -W resolves it by letting composer downgrade PHPUnit');
        $this->line('  to 12.5.30. Whether to accept that downgrade is your call:');
        $this->newLine();
        $this->line("    as proposed:  {$command}");
        $this->line("    with -W:      {$command} -W");
    }

    /**
     * Package names already present in the project. installed.json records what
     * is actually on disk; composer.lock is the fallback for a project whose
     * dependencies have not been installed yet.
     *
     * @return list<string>
     */
    private function installedPackages(Filesystem $files, string $basePath): array
    {
        $installed = $basePath.'/vendor/composer/installed.json';

        if ($files->exists($installed)) {
            $decoded = json_decode($files->get($installed), true);
            $packages = is_array($decoded) ? ($decoded['packages'] ?? []) : [];

            if (is_array($packages)) {
                return $this->packageNames($packages);
            }
        }

        $lock = $basePath.'/composer.lock';

        if (! $files->exists($lock)) {
            return [];
        }

        $decoded = json_decode($files->get($lock), true);

        if (! is_array($decoded)) {
            return [];
        }

        $packages = array_merge(
            is_array($decoded['packages'] ?? null) ? $decoded['packages'] : [],
            is_array($decoded['packages-dev'] ?? null) ? $decoded['packages-dev'] : [],
        );

        return $this->packageNames($packages);
    }

    /**
     * @param  array<array-key, mixed>  $packages
     * @return list<string>
     */
    private function packageNames(array $packages): array
    {
        $names = [];

        foreach ($packages as $package) {
            if (is_array($package) && is_string($package['name'] ?? null)) {
                $names[] = $package['name'];
            }
        }

        return $names;
    }

    private function configureGitHook(string $basePath): void
    {
        if (! is_dir($basePath.'/.git')) {
            return;
        }

        Process::path($basePath)->run('git config core.hooksPath .githooks');
    }

    /**
     * @param  list<string>  $manualSteps
     */
    private function printNextSteps(InstallOptions $options, ?string $pendingComposer, array $manualSteps): void
    {
        $steps = [];

        if ($pendingComposer !== null) {
            $steps[] = 'Install the selected tooling: `'.$pendingComposer.'`';
        }

        if ($options->hasHook('pre-commit')) {
            $steps[] = 'Run `composer install` to wire the git pre-commit hook.';
        }

        if ($options->stack->hasFrontendTooling()) {
            $steps[] = 'Run `npm install` to pull the frontend devDependencies.';
        }

        if ($options->pint) {
            $steps[] = 'Run `composer lint` once to normalize Laravel\'s shipped files before your first commit.';
        }

        foreach ($manualSteps as $step) {
            $steps[] = $step;
        }

        if ($options->wants('rules')) {
            $steps[] = 'Fill in the TODO placeholders in CLAUDE.md.';
            $steps[] = 'Read the conventions in .claude/rules/ and adapt them to how you actually work.';
        }

        if ($options->deployCommand === '' && $options->hasWorkflowSkill('deploy')) {
            $steps[] = 'Record the deploy command in .claude/skills/deploy/SKILL.md (it has a TODO).';
        }

        if ($options->tests && $options->coverageMin !== null) {
            $steps[] = 'Install a coverage driver (`pcov` or Xdebug) to enforce the '.$options->coverageMin.'% gate.';
        }

        $this->newLine();
        $this->components->info('claude-kit installed. Next steps:');
        $this->components->bulletList($steps);
    }
}
