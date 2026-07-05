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
        ->expectsConfirmation('Use Laravel Pint for code style?', 'yes')
        ->expectsConfirmation('Use PHPStan (Larastan) for static analysis?', 'yes')
        ->expectsChoice('Which PHPStan level?', '9', array_map('strval', range(0, 9)))
        ->expectsConfirmation('Enable phpstan-strict-rules?', 'no')
        ->expectsConfirmation('Set up a test gate?', 'yes')
        ->expectsChoice('Which test runner?', 'pest', ['pest' => 'Pest', 'phpunit' => 'PHPUnit'])
        ->expectsQuestion('Minimum coverage % to enforce (blank = do not enforce)', '90')
        ->expectsConfirmation('Add the architecture test suite (tests/Arch)?', 'yes')
        ->expectsChoice('Which hooks should enforce the gate?', ['stop', 'pre-commit'], [
            'stop' => 'Claude Code Stop hook (runs the gate on every turn)',
            'pre-commit' => 'Git pre-commit hook',
            'feature-docs' => 'Feature-doc requirement (part of the Stop hook)',
        ])
        ->expectsChoice('What else should I scaffold?', ['rules'], [
            'rules' => 'CLAUDE.md engineering rules',
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
        ->expectsConfirmation('Search skills.sh for additional skills to install?', 'yes')
        ->assertSuccessful();

    $neon = $files->get($dir.'/phpstan.neon');
    $manifest = json_decode($files->get($dir.'/.claude-kit.json'), true);

    expect($neon)->toContain('level: 9')
        ->and($neon)->not->toContain('phpstan-strict-rules')
        ->and($files->exists($dir.'/CLAUDE.md'))->toBeTrue()
        ->and($files->exists($dir.'/pint.json'))->toBeTrue()
        ->and($files->exists($dir.'/.mcp.json'))->toBeFalse()
        ->and($manifest['tests']['coverage_min'])->toBe(90);
});
