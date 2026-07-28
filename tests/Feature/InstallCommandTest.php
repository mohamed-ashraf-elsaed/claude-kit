<?php

declare(strict_types=1);

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Process;
use Illuminate\Testing\PendingCommand;

function hostProject(?array $installedPackages = null): string
{
    $dir = temp_project();
    $files = new Filesystem;
    $files->ensureDirectoryExists($dir.'/app');
    $files->put($dir.'/composer.json', json_encode(['name' => 'acme/app', 'scripts' => []]));

    if ($installedPackages !== null) {
        $files->ensureDirectoryExists($dir.'/vendor/composer');
        $files->put($dir.'/vendor/composer/installed.json', json_encode([
            'packages' => array_map(static fn (string $n): array => ['name' => $n], $installedPackages),
            'dev' => true,
        ]));
    }

    app()->setBasePath($dir);

    return $dir;
}

it('scaffolds files into the project when run non-interactively', function (): void {
    $dir = temp_project();
    $files = new Filesystem;
    $files->ensureDirectoryExists($dir.'/app');
    $files->put($dir.'/composer.json', json_encode(['name' => 'acme/app', 'scripts' => []]));

    $this->app->setBasePath($dir);

    $this->artisan('claude-kit:install', ['--stack' => 'inertia-vue', '--no-interaction' => true])
        ->assertSuccessful();

    expect($files->exists($dir.'/.claude/settings.json'))->toBeTrue()
        ->and($files->exists($dir.'/CLAUDE.md'))->toBeTrue()
        ->and($files->exists($dir.'/phpstan.neon'))->toBeTrue()
        ->and($files->exists($dir.'/.claude/claude-kit.json'))->toBeTrue()
        ->and($files->exists($dir.'/.claude/rules/clean-code.md'))->toBeTrue()
        ->and($files->exists($dir.'/.claude/agents/design-reviewer.md'))->toBeTrue();
});

it('leaves every existing file alone when run non-interactively', function (): void {
    $dir = temp_project();
    $files = new Filesystem;
    $files->ensureDirectoryExists($dir.'/app');
    $files->put($dir.'/composer.json', json_encode(['name' => 'acme/app', 'scripts' => []]));
    $files->put($dir.'/pint.json', 'MINE');

    $this->app->setBasePath($dir);

    $this->artisan('claude-kit:install', ['--stack' => 'none', '--no-interaction' => true])
        ->assertSuccessful();

    expect($files->get($dir.'/pint.json'))->toBe('MINE');
});

it('prints the composer command instead of running it when non-interactive', function (): void {
    hostProject(installedPackages: []);
    Process::fake();

    $this->artisan('claude-kit:install', ['--stack' => 'none', '--no-interaction' => true])
        ->expectsOutputToContain('composer require --dev laravel/pint larastan/larastan phpstan/phpstan-strict-rules pestphp/pest pestphp/pest-plugin-laravel')
        ->assertSuccessful();

    Process::assertNothingRan();
});

it('excludes packages the project already has', function (): void {
    hostProject(installedPackages: ['laravel/pint', 'pestphp/pest', 'pestphp/pest-plugin-laravel', 'laravel/framework']);
    Process::fake();

    $this->artisan('claude-kit:install', ['--stack' => 'none', '--no-interaction' => true])
        ->expectsOutputToContain('composer require --dev larastan/larastan phpstan/phpstan-strict-rules')
        ->assertSuccessful();
});

it('says nothing about composer when every selected tool is already installed', function (): void {
    hostProject(installedPackages: [
        'laravel/pint', 'larastan/larastan', 'phpstan/phpstan-strict-rules',
        'pestphp/pest', 'pestphp/pest-plugin-laravel',
    ]);
    Process::fake();

    $this->artisan('claude-kit:install', ['--stack' => 'none', '--no-interaction' => true])
        ->doesntExpectOutputToContain('composer require --dev')
        ->assertSuccessful();
});

it('falls back to composer.lock when the project has no vendor directory', function (): void {
    $dir = hostProject();
    (new Filesystem)->put($dir.'/composer.lock', json_encode([
        'packages' => [['name' => 'laravel/framework']],
        'packages-dev' => [['name' => 'laravel/pint'], ['name' => 'pestphp/pest']],
    ]));
    Process::fake();

    $this->artisan('claude-kit:install', ['--stack' => 'none', '--no-interaction' => true])
        ->expectsOutputToContain('composer require --dev larastan/larastan phpstan/phpstan-strict-rules pestphp/pest-plugin-laravel')
        ->assertSuccessful();
});

it('runs composer when the prompt is accepted', function (): void {
    hostProject(installedPackages: ['laravel/pint', 'larastan/larastan', 'phpstan/phpstan-strict-rules', 'pestphp/pest']);
    // Fake everything: an unmatched pattern would let composer run for real.
    Process::fake();

    $this->artisan('claude-kit:install', ['--stack' => 'none', '--force' => true])
        ->expectsConfirmation('Use Laravel Pint for code style?', 'no')
        ->expectsConfirmation('Use PHPStan for static analysis?', 'no')
        ->expectsConfirmation('Set up a test gate?', 'yes')
        ->expectsChoice('Which test runner?', 'pest', ['pest' => 'Pest', 'phpunit' => 'PHPUnit'])
        ->expectsQuestion('Minimum coverage % to enforce (blank = do not enforce)', '')
        ->expectsConfirmation('Add the architecture test suite (tests/Arch)?', 'no')
        ->expectsChoice('Which layers does this project use?', [], [
            'services' => 'Services (app/Services) — business logic',
            'repositories' => 'Repositories (app/Repositories) — named persistence queries',
            'data-objects' => 'Data Objects (app/Data) — readonly DTOs',
            'value-objects' => 'Value Objects (app/ValueObjects) — self-validating types',
            'actions' => 'Actions (app/Actions) — one invokable class per operation',
            'contracts' => 'Contracts (app/Contracts) — interfaces for boundaries',
        ])
        ->expectsChoice('How strict should the clean-code rule be?', 'balanced', [
            'strict' => 'strict — hard limits, no exceptions',
            'balanced' => 'balanced — limits with documented escape hatches',
            'relaxed' => 'relaxed — direction, not thresholds',
        ])
        ->expectsConfirmation('Validate the quality gate when Claude finishes a turn (Stop hook)?', 'no')
        ->expectsConfirmation('Install the git pre-commit hook?', 'no')
        ->expectsConfirmation('Require a feature doc for changed code (part of the Stop hook)?', 'no')
        ->expectsChoice('Which read-only review agents should I install?', [], [
            'design-reviewer' => 'design-reviewer — checks the result against the stated design/plan',
            'docs-reviewer' => 'docs-reviewer — checks the result against supplied docs',
        ])
        ->expectsChoice('Which workflow skills should I install?', [], [
            'commit' => '/commit — stage and commit to this project\'s convention',
            'deploy' => '/deploy — run the deploy, including per-feature steps',
            'dead-code' => '/dead-code — detect, prove, then remove unused code',
            'codemap' => '/codemap — create or refresh CODEMAP.md',
        ])
        ->expectsChoice('Which always-on behavioural rules should I install?', [], [
            'anti-overengineering' => 'Build the smallest thing that satisfies the requirement',
            'anti-assumption' => 'Verify or ask — never invent a detail',
        ])
        ->expectsConfirmation('Document the git-worktree workflow for parallel features?', 'no')
        ->expectsConfirmation('Install the concise response style (and select it)?', 'no')
        ->expectsChoice('What else should I scaffold?', [], [
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
        // Only pest-plugin-laravel is missing from this project.
        ->expectsConfirmation('Run `composer require --dev pestphp/pest-plugin-laravel` now?', 'yes')
        ->expectsConfirmation('Search skills.sh for additional skills to install?', 'no')
        ->assertSuccessful();

    Process::assertRan('composer require --dev pestphp/pest-plugin-laravel');
});

/**
 * Drives the interactive flow with everything switched off except a Pest test
 * gate, so exactly one package is missing and the composer step is reached.
 */
function installWithOnlyPestMissing(): PendingCommand
{
    hostProject(installedPackages: ['laravel/pint', 'larastan/larastan', 'phpstan/phpstan-strict-rules', 'pestphp/pest']);

    return test()->artisan('claude-kit:install', ['--stack' => 'none', '--force' => true])
        ->expectsConfirmation('Use Laravel Pint for code style?', 'no')
        ->expectsConfirmation('Use PHPStan for static analysis?', 'no')
        ->expectsConfirmation('Set up a test gate?', 'yes')
        ->expectsChoice('Which test runner?', 'pest', ['pest' => 'Pest', 'phpunit' => 'PHPUnit'])
        ->expectsQuestion('Minimum coverage % to enforce (blank = do not enforce)', '')
        ->expectsConfirmation('Add the architecture test suite (tests/Arch)?', 'no')
        ->expectsChoice('Which layers does this project use?', [], [
            'services' => 'Services (app/Services) — business logic',
            'repositories' => 'Repositories (app/Repositories) — named persistence queries',
            'data-objects' => 'Data Objects (app/Data) — readonly DTOs',
            'value-objects' => 'Value Objects (app/ValueObjects) — self-validating types',
            'actions' => 'Actions (app/Actions) — one invokable class per operation',
            'contracts' => 'Contracts (app/Contracts) — interfaces for boundaries',
        ])
        ->expectsChoice('How strict should the clean-code rule be?', 'balanced', [
            'strict' => 'strict — hard limits, no exceptions',
            'balanced' => 'balanced — limits with documented escape hatches',
            'relaxed' => 'relaxed — direction, not thresholds',
        ])
        ->expectsConfirmation('Validate the quality gate when Claude finishes a turn (Stop hook)?', 'no')
        ->expectsConfirmation('Install the git pre-commit hook?', 'no')
        ->expectsConfirmation('Require a feature doc for changed code (part of the Stop hook)?', 'no')
        ->expectsChoice('Which read-only review agents should I install?', [], [
            'design-reviewer' => 'design-reviewer — checks the result against the stated design/plan',
            'docs-reviewer' => 'docs-reviewer — checks the result against supplied docs',
        ])
        ->expectsChoice('Which workflow skills should I install?', [], [
            'commit' => '/commit — stage and commit to this project\'s convention',
            'deploy' => '/deploy — run the deploy, including per-feature steps',
            'dead-code' => '/dead-code — detect, prove, then remove unused code',
            'codemap' => '/codemap — create or refresh CODEMAP.md',
        ])
        ->expectsChoice('Which always-on behavioural rules should I install?', [], [
            'anti-overengineering' => 'Build the smallest thing that satisfies the requirement',
            'anti-assumption' => 'Verify or ask — never invent a detail',
        ])
        ->expectsConfirmation('Document the git-worktree workflow for parallel features?', 'no')
        ->expectsConfirmation('Install the concise response style (and select it)?', 'no')
        ->expectsChoice('What else should I scaffold?', [], [
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
        ->expectsConfirmation('Run `composer require --dev pestphp/pest-plugin-laravel` now?', 'yes')
        ->expectsConfirmation('Search skills.sh for additional skills to install?', 'no');
}

it('explains the upstream Pest conflict and offers both commands', function (): void {
    $conflict = <<<'OUT'
    Your requirements could not be resolved to an installable set of packages.
      - pestphp/pest[v4.7.4, ..., v4.7.5] conflict with phpunit/phpunit >12.5.30.
      - pestphp/pest[v5.0.0, ..., v5.0.1] require phpunit/phpunit ^13.2.4 -> found phpunit/phpunit[13.2.4] but it conflicts with your root composer.json require (^12.5.12).
    Installation failed, reverting ./composer.json and ./composer.lock to their original content.
    OUT;

    Process::fake(['*' => Process::result(errorOutput: $conflict, exitCode: 2)]);

    installWithOnlyPestMissing()
        ->expectsOutputToContain('current Pest releases conflict with phpunit/phpunit newer than 12.5.30')
        ->expectsOutputToContain('composer require --dev pestphp/pest-plugin-laravel -W')
        ->expectsOutputToContain('as proposed:  composer require --dev pestphp/pest-plugin-laravel')
        ->assertSuccessful();
});

it('does not explain the Pest conflict for an unrelated composer failure', function (): void {
    Process::fake(['*' => Process::result(errorOutput: 'Could not authenticate against github.com', exitCode: 1)]);

    installWithOnlyPestMissing()
        ->expectsOutputToContain('Could not authenticate against github.com')
        ->doesntExpectOutputToContain('known conflict upstream of claude-kit')
        ->doesntExpectOutputToContain('-W')
        ->assertSuccessful();
});

it('fails on an unknown stack', function (): void {
    $dir = temp_project();
    $this->app->setBasePath($dir);

    $this->artisan('claude-kit:install', ['--stack' => 'svelte'])
        ->assertFailed();
});
