<?php

declare(strict_types=1);

namespace MohamedAshrafElsaed\ClaudeKit\Support;

/**
 * The fully-resolved set of choices for one install run. Built either from the
 * interactive prompts in InstallCommand or from defaults() (non-interactive /
 * tests), then consumed by the Installer.
 */
final readonly class InstallOptions
{
    /**
     * @param  list<string>  $hooks  subset of: stop, pre-commit, feature-docs
     * @param  list<string>  $skills  skill directory names to publish from the bundled set
     * @param  list<string>  $scaffolding  subset of: rules, docs, editorconfig, mcp, ci
     */
    public function __construct(
        public FrontendStack $stack,
        public bool $pint,
        public bool $phpstan,
        public int $phpstanLevel,
        public bool $phpstanStrict,
        public bool $tests,
        public TestTool $testTool,
        public ?int $coverageMin,
        public bool $archTests,
        public array $hooks,
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
            tests: true,
            testTool: TestTool::Pest,
            coverageMin: 80,
            archTests: true,
            hooks: ['stop', 'pre-commit', 'feature-docs'],
            skills: $stack->skills(),
            scaffolding: ['rules', 'docs', 'editorconfig', 'mcp', 'ci'],
            force: $force,
        );
    }

    public function hasHook(string $hook): bool
    {
        return in_array($hook, $this->hooks, true);
    }

    public function wants(string $item): bool
    {
        return in_array($item, $this->scaffolding, true);
    }

    public function runsArchitectureTests(): bool
    {
        return $this->tests && $this->archTests && $this->testTool->supportsArchitectureTests();
    }
}
