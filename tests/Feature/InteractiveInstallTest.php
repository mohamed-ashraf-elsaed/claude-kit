<?php

declare(strict_types=1);

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Process;

it('walks the interactive configurator and scaffolds the chosen setup', function (): void {
    $dir = temp_project();
    $files = new Filesystem;
    $files->ensureDirectoryExists($dir.'/app');
    $files->put($dir.'/composer.json', json_encode(['name' => 'acme/app', 'scripts' => []]));
    $this->app->setBasePath($dir);

    // The skills.sh step will report npx as unavailable and bail gracefully.
    Process::fake(['npx --version' => Process::result(exitCode: 1)]);

    $this->artisan('claude-kit:install', ['--stack' => 'none'])
        // Code style & static analysis
        ->expectsConfirmation('Use Laravel Pint for code style?', 'yes')
        ->expectsConfirmation('Use PHPStan for static analysis?', 'yes')
        ->expectsChoice('Which PHPStan level?', '9', array_map('strval', range(0, 9)))
        ->expectsConfirmation('Add Larastan (Laravel-aware analysis)?', 'no')
        ->expectsConfirmation('Enable phpstan-strict-rules?', 'no')
        ->expectsConfirmation('Analyse every PHP file Claude edits, as it edits it?', 'yes')
        ->expectsChoice('When that check fails, what should happen?', 'warn', [
            'block' => 'Block — feed the errors back so Claude fixes them before continuing',
            'warn' => 'Warn only — show a warning and let Claude carry on',
        ])
        // Tests
        ->expectsConfirmation('Set up a test gate?', 'yes')
        ->expectsChoice('Which test runner?', 'pest', ['pest' => 'Pest', 'phpunit' => 'PHPUnit'])
        ->expectsQuestion('Minimum coverage % to enforce (blank = do not enforce)', '90')
        ->expectsConfirmation('Add the architecture test suite (tests/Arch)?', 'yes')
        // Architecture
        ->expectsChoice('Which layers does this project use?', ['services', 'value-objects'], [
            'services' => 'Services (app/Services) — business logic',
            'repositories' => 'Repositories (app/Repositories) — named persistence queries',
            'data-objects' => 'Data Objects (app/Data) — readonly DTOs',
            'value-objects' => 'Value Objects (app/ValueObjects) — self-validating types',
            'actions' => 'Actions (app/Actions) — one invokable class per operation',
            'contracts' => 'Contracts (app/Contracts) — interfaces for boundaries',
        ])
        ->expectsChoice('How strict should the clean-code rule be?', 'strict', [
            'strict' => 'strict — hard limits, no exceptions',
            'balanced' => 'balanced — limits with documented escape hatches',
            'relaxed' => 'relaxed — direction, not thresholds',
        ])
        // Enforcement hooks
        ->expectsConfirmation('Validate the quality gate when Claude finishes a turn (Stop hook)?', 'yes')
        ->expectsChoice('Which checks must pass before a turn may finish?', ['phpstan', 'tests'], [
            'pint' => 'Pint (code style)',
            'phpstan' => 'PHPStan (static analysis)',
            'tests' => 'Test suite',
        ])
        ->expectsChoice('How many consecutive clean runs before the turn is allowed to end?', '3', array_map('strval', range(1, 5)))
        ->expectsConfirmation('Install the git pre-commit hook?', 'yes')
        ->expectsConfirmation('Require a feature doc for changed code (part of the Stop hook)?', 'no')
        // Reviewers, skills & rules
        ->expectsChoice('Which read-only review agents should I install?', ['docs-reviewer'], [
            'design-reviewer' => 'design-reviewer — checks the result against the stated design/plan',
            'docs-reviewer' => 'docs-reviewer — checks the result against supplied docs',
        ])
        ->expectsChoice('Which workflow skills should I install?', ['commit', 'deploy'], [
            'commit' => '/commit — stage and commit to this project\'s convention',
            'deploy' => '/deploy — run the deploy, including per-feature steps',
            'dead-code' => '/dead-code — detect, prove, then remove unused code',
            'codemap' => '/codemap — create or refresh CODEMAP.md',
        ])
        ->expectsQuestion('The exact deploy command (blank leaves a TODO — nothing is invented)', '')
        ->expectsChoice('Which commit message convention?', 'custom', [
            'conventional' => 'Conventional Commits (feat:, fix:, refactor: …)',
            'custom' => 'Something else — I will describe it',
        ])
        ->expectsQuestion('Describe the convention (used verbatim, exactly as typed)', '[TEAM-123] what changed')
        ->expectsChoice('Which always-on behavioural rules should I install?', ['anti-assumption'], [
            'anti-overengineering' => 'Build the smallest thing that satisfies the requirement',
            'anti-assumption' => 'Verify or ask — never invent a detail',
        ])
        ->expectsConfirmation('Document the git-worktree workflow for parallel features?', 'no')
        ->expectsConfirmation('Install the concise response style (and select it)?', 'no')
        // Scaffolding
        ->expectsChoice('What else should I scaffold?', ['rules'], [
            'rules' => 'CLAUDE.md + .claude/rules/ conventions',
            'docs' => 'Feature-doc templates (features/)',
            'editorconfig' => '.editorconfig + .gitattributes',
            'mcp' => 'Laravel Boost MCP (.mcp.json)',
            'ci' => 'GitHub Actions workflows',
        ])
        ->expectsChoice('Which bundled skills should I install?', [], [
            'ai-sdk-development' => 'ai-sdk-development',
            'inertia-vue-development' => 'inertia-vue-development',
            'laravel-best-practices' => 'laravel-best-practices',
            'pest-testing' => 'pest-testing',
            'socialite-development' => 'socialite-development',
            'tailwindcss-development' => 'tailwindcss-development',
            'wayfinder-development' => 'wayfinder-development',
        ])
        // Larastan and strict-rules were declined, so neither is proposed.
        ->expectsConfirmation('Run `composer require --dev laravel/pint phpstan/phpstan pestphp/pest pestphp/pest-plugin-laravel` now?', 'no')
        ->expectsConfirmation('Search skills.sh for additional skills to install?', 'yes')
        ->expectsOutputToContain('composer require --dev laravel/pint phpstan/phpstan pestphp/pest pestphp/pest-plugin-laravel')
        ->assertSuccessful();

    $neon = $files->get($dir.'/phpstan.neon');
    $manifest = json_decode($files->get($dir.'/.claude/claude-kit.json'), true);

    expect($neon)->toContain('level: 9')
        ->and($neon)->not->toContain('phpstan-strict-rules')
        ->and($neon)->not->toContain('larastan')
        ->and($manifest['tests']['coverage_min'])->toBe(90)
        ->and($manifest['hooks']['checks'])->toBe(['phpstan', 'tests'])
        ->and($manifest['hooks']['min_passes'])->toBe(3)
        ->and($manifest['hooks']['feature_docs'])->toBeFalse()
        ->and($manifest['architecture'])->toBe(['services', 'value-objects'])
        ->and($manifest['rules']['clean_code'])->toBe('strict')
        ->and($manifest['phpstan']['hook'])->toBe('warn')
        // Chosen: rules only, no docs/editorconfig/mcp/ci; no bundled skills.
        ->and($files->exists($dir.'/CLAUDE.md'))->toBeTrue()
        ->and($files->exists($dir.'/pint.json'))->toBeTrue()
        ->and($files->exists($dir.'/.mcp.json'))->toBeFalse()
        ->and($files->exists($dir.'/.editorconfig'))->toBeFalse()
        ->and($files->exists($dir.'/.claude/output-styles/concise.md'))->toBeFalse()
        // Declined rules and agents must not appear.
        ->and($files->exists($dir.'/.claude/rules/worktrees.md'))->toBeFalse()
        ->and($files->exists($dir.'/.claude/rules/anti-overengineering.md'))->toBeFalse()
        ->and($files->exists($dir.'/.claude/rules/anti-assumption.md'))->toBeTrue()
        ->and($files->exists($dir.'/.claude/agents/design-reviewer.md'))->toBeFalse()
        ->and($files->exists($dir.'/.claude/agents/docs-reviewer.md'))->toBeTrue()
        ->and($files->exists($dir.'/.claude/skills/dead-code/SKILL.md'))->toBeFalse()
        // Custom convention, verbatim; deploy left as a TODO.
        ->and($files->get($dir.'/.claude/skills/commit/SKILL.md'))->toContain('[TEAM-123] what changed')
        ->and($files->get($dir.'/.claude/skills/deploy/SKILL.md'))->toContain('TODO — no deploy command')
        // Feature docs were declined, so CLAUDE.md must not demand them.
        ->and($files->get($dir.'/CLAUDE.md'))->not->toContain('Feature documentation');
});
