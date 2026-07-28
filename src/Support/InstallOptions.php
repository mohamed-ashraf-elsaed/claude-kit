<?php

declare(strict_types=1);

namespace MohamedAshrafElsaed\ClaudeKit\Support;

/**
 * The fully-resolved set of choices for one install run. Built either from the
 * interactive prompts in InstallCommand, from defaults() (non-interactive /
 * tests), or from fromManifest() (claude-kit:update), then consumed by the
 * Installer.
 */
final readonly class InstallOptions
{
    public const LAYERS = ['services', 'repositories', 'data-objects', 'value-objects', 'actions', 'contracts'];

    public const CHECKS = ['pint', 'phpstan', 'tests'];

    public const AGENTS = ['design-reviewer', 'docs-reviewer'];

    public const WORKFLOW_SKILLS = ['commit', 'deploy', 'dead-code', 'codemap'];

    public const BEHAVIORAL_RULES = ['anti-overengineering', 'anti-assumption'];

    /**
     * @param  string  $phpstanHook  one of: off, block, warn
     * @param  list<string>  $architecture  subset of self::LAYERS
     * @param  list<string>  $hooks  subset of: stop, pre-commit, feature-docs
     * @param  list<string>  $validationChecks  subset of self::CHECKS — what the Stop hook runs
     * @param  int  $minPasses  consecutive clean runs the Stop hook requires (1-5)
     * @param  list<string>  $agents  subset of self::AGENTS
     * @param  list<string>  $workflowSkills  subset of self::WORKFLOW_SKILLS
     * @param  string  $deployCommand  blank publishes /deploy with a TODO placeholder
     * @param  string|null  $commitConvention  null means Conventional Commits
     * @param  list<string>  $behavioralRules  subset of self::BEHAVIORAL_RULES
     * @param  list<string>  $skills  skill directory names to publish from the bundled set
     * @param  list<string>  $scaffolding  subset of: rules, docs, editorconfig, mcp, ci
     */
    public function __construct(
        public FrontendStack $stack,
        public bool $pint,
        public bool $phpstan,
        public int $phpstanLevel,
        public bool $phpstanStrict,
        public bool $phpstanLarastan,
        public string $phpstanHook,
        public bool $tests,
        public TestTool $testTool,
        public ?int $coverageMin,
        public bool $archTests,
        public array $architecture,
        public CleanCodePreset $cleanCode,
        public array $hooks,
        public array $validationChecks,
        public int $minPasses,
        public array $agents,
        public array $workflowSkills,
        public string $deployCommand,
        public ?string $commitConvention,
        public array $behavioralRules,
        public bool $outputStyle,
        public bool $worktreeRule,
        public array $skills,
        public array $scaffolding,
        public bool $force,
    ) {}

    public static function defaults(FrontendStack $stack, bool $force = false): self
    {
        return new self(
            stack: $stack,
            pint: true,
            phpstan: true,
            phpstanLevel: 7,
            phpstanStrict: true,
            phpstanLarastan: true,
            phpstanHook: 'block',
            tests: true,
            testTool: TestTool::Pest,
            coverageMin: null,
            archTests: true,
            architecture: self::LAYERS,
            cleanCode: CleanCodePreset::Balanced,
            hooks: ['stop', 'pre-commit', 'feature-docs'],
            validationChecks: self::CHECKS,
            minPasses: 2,
            agents: self::AGENTS,
            workflowSkills: self::WORKFLOW_SKILLS,
            deployCommand: '',
            commitConvention: null,
            behavioralRules: self::BEHAVIORAL_RULES,
            outputStyle: true,
            worktreeRule: true,
            skills: $stack->skills(),
            scaffolding: ['rules', 'docs', 'editorconfig', 'mcp', 'ci'],
            force: $force,
        );
    }

    /**
     * Rebuild the selections recorded by a previous install so
     * `claude-kit:update` can re-publish exactly what was chosen then. Anything
     * missing from an older manifest falls back to the default for that choice.
     *
     * @param  array<string, mixed>  $manifest
     */
    public static function fromManifest(array $manifest, FrontendStack $stack, bool $force = false): self
    {
        $defaults = self::defaults($stack, $force);
        $phpstan = self::section($manifest, 'phpstan');
        $tests = self::section($manifest, 'tests');
        $hooks = self::section($manifest, 'hooks');
        $skills = self::section($manifest, 'skills');
        $rules = self::section($manifest, 'rules');

        $preset = CleanCodePreset::tryFrom(self::string($rules, 'clean_code', $defaults->cleanCode->value));
        $tool = TestTool::tryFrom(self::string($tests, 'tool', $defaults->testTool->value));
        // A recorded null means "do not enforce coverage", so an explicit null
        // must not fall through to the default the way a missing key does.
        $coverage = array_key_exists('coverage_min', $tests) ? $tests['coverage_min'] : $defaults->coverageMin;
        $convention = $skills['commit_convention'] ?? null;

        return new self(
            stack: $stack,
            pint: self::bool($manifest, 'pint', $defaults->pint),
            phpstan: self::bool($phpstan, 'enabled', $defaults->phpstan),
            phpstanLevel: is_int($phpstan['level'] ?? null) ? $phpstan['level'] : $defaults->phpstanLevel,
            phpstanStrict: self::bool($phpstan, 'strict', $defaults->phpstanStrict),
            phpstanLarastan: self::bool($phpstan, 'larastan', $defaults->phpstanLarastan),
            phpstanHook: self::string($phpstan, 'hook', $defaults->phpstanHook),
            tests: self::bool($tests, 'enabled', $defaults->tests),
            testTool: $tool ?? $defaults->testTool,
            coverageMin: is_int($coverage) ? $coverage : null,
            archTests: self::bool($tests, 'arch', $defaults->archTests),
            architecture: self::strings($manifest, 'architecture', $defaults->architecture),
            cleanCode: $preset ?? $defaults->cleanCode,
            hooks: self::strings($hooks, 'enabled', $defaults->hooks),
            validationChecks: self::strings($hooks, 'checks', $defaults->validationChecks),
            minPasses: is_int($hooks['min_passes'] ?? null) ? $hooks['min_passes'] : $defaults->minPasses,
            agents: self::strings($manifest, 'agents', $defaults->agents),
            workflowSkills: self::strings($skills, 'workflow', $defaults->workflowSkills),
            deployCommand: self::string($skills, 'deploy_command', $defaults->deployCommand),
            commitConvention: is_string($convention) ? $convention : null,
            behavioralRules: self::strings($rules, 'behavioral', $defaults->behavioralRules),
            outputStyle: self::bool($manifest, 'output_style', $defaults->outputStyle),
            worktreeRule: self::bool($rules, 'worktrees', $defaults->worktreeRule),
            skills: self::strings($skills, 'bundled', $defaults->skills),
            scaffolding: self::strings($manifest, 'scaffolding', $defaults->scaffolding),
            force: $force,
        );
    }

    /**
     * The composer packages the selected options actually need. claude-kit no
     * longer requires the tools it configures, so the installer offers exactly
     * this list — nothing for a tool that was declined.
     *
     * @return list<string>
     */
    public function requiredDevPackages(): array
    {
        $packages = [];

        if ($this->pint) {
            $packages[] = 'laravel/pint';
        }

        if ($this->phpstan) {
            // Larastan depends on PHPStan; without it PHPStan is needed directly.
            $packages[] = $this->phpstanLarastan ? 'larastan/larastan' : 'phpstan/phpstan';

            if ($this->phpstanStrict) {
                $packages[] = 'phpstan/phpstan-strict-rules';
            }
        }

        if ($this->tests) {
            $packages = $this->testTool === TestTool::Pest
                ? [...$packages, 'pestphp/pest', 'pestphp/pest-plugin-laravel']
                : [...$packages, 'phpunit/phpunit'];
        }

        return $packages;
    }

    public function hasHook(string $hook): bool
    {
        return in_array($hook, $this->hooks, true);
    }

    public function wants(string $item): bool
    {
        return in_array($item, $this->scaffolding, true);
    }

    public function hasLayer(string $layer): bool
    {
        return in_array($layer, $this->architecture, true);
    }

    public function hasAgent(string $agent): bool
    {
        return in_array($agent, $this->agents, true);
    }

    public function hasWorkflowSkill(string $skill): bool
    {
        return in_array($skill, $this->workflowSkills, true);
    }

    public function hasBehavioralRule(string $rule): bool
    {
        return in_array($rule, $this->behavioralRules, true);
    }

    public function runsArchitectureTests(): bool
    {
        return $this->tests && $this->archTests && $this->testTool->supportsArchitectureTests();
    }

    public function installsPhpstanHook(): bool
    {
        return $this->phpstan && $this->phpstanHook !== 'off';
    }

    /**
     * @param  array<string, mixed>  $source
     * @return array<string, mixed>
     */
    private static function section(array $source, string $key): array
    {
        $value = $source[$key] ?? [];

        /** @var array<string, mixed> */
        return is_array($value) ? $value : [];
    }

    /**
     * @param  array<string, mixed>  $source
     */
    private static function bool(array $source, string $key, bool $default): bool
    {
        $value = $source[$key] ?? null;

        return is_bool($value) ? $value : $default;
    }

    /**
     * @param  array<string, mixed>  $source
     */
    private static function string(array $source, string $key, string $default): string
    {
        $value = $source[$key] ?? null;

        return is_string($value) ? $value : $default;
    }

    /**
     * @param  array<string, mixed>  $source
     * @param  list<string>  $default
     * @return list<string>
     */
    private static function strings(array $source, string $key, array $default): array
    {
        $value = $source[$key] ?? null;

        if (! is_array($value)) {
            return $default;
        }

        return array_values(array_filter($value, 'is_string'));
    }
}
