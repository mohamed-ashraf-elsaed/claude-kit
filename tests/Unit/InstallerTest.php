<?php

declare(strict_types=1);

use Illuminate\Filesystem\Filesystem;
use MohamedAshrafElsaed\ClaudeKit\Support\FrontendStack;
use MohamedAshrafElsaed\ClaudeKit\Support\Installer;
use MohamedAshrafElsaed\ClaudeKit\Support\InstallOptions;
use MohamedAshrafElsaed\ClaudeKit\Support\TestTool;

function installer(string $dir): Installer
{
    return new Installer(new Filesystem, $dir, stubs_path());
}

function laravelSkeleton(string $dir): void
{
    $files = new Filesystem;
    $files->ensureDirectoryExists($dir.'/app');
    $files->put($dir.'/composer.json', json_encode(['name' => 'acme/app', 'scripts' => []]));
}

function readJson(string $path): array
{
    return json_decode((new Filesystem)->get($path), true);
}

it('installs the full kit for an Inertia + Vue project', function (): void {
    $dir = temp_project();
    laravelSkeleton($dir);

    installer($dir)->run(InstallOptions::defaults(FrontendStack::InertiaVue), 'Acme');

    $files = new Filesystem;
    expect($files->exists($dir.'/.claude/settings.json'))->toBeTrue()
        ->and($files->exists($dir.'/.mcp.json'))->toBeTrue()
        ->and($files->exists($dir.'/.claude/skills/inertia-vue-development/SKILL.md'))->toBeTrue()
        ->and($files->exists($dir.'/CLAUDE.md'))->toBeTrue()
        ->and($files->exists($dir.'/phpstan.neon'))->toBeTrue()
        ->and($files->exists($dir.'/pint.json'))->toBeTrue()
        ->and($files->exists($dir.'/tests/Arch/ArchTest.php'))->toBeTrue()
        ->and($files->exists($dir.'/.githooks/pre-commit'))->toBeTrue()
        ->and($files->exists($dir.'/eslint.config.js'))->toBeTrue()
        ->and($files->exists($dir.'/.github/workflows/tests.yml'))->toBeTrue()
        ->and($files->exists($dir.'/.claude-kit.json'))->toBeTrue();
});

it('renders the chosen PHPStan level and strict rules into phpstan.neon', function (): void {
    $dir = temp_project();
    laravelSkeleton($dir);

    $options = new InstallOptions(
        stack: FrontendStack::None,
        pint: false,
        phpstan: true,
        phpstanLevel: 9,
        phpstanStrict: false,
        tests: false,
        testTool: TestTool::Pest,
        coverageMin: null,
        archTests: false,
        hooks: [],
        skills: [],
        scaffolding: [],
        force: false,
    );

    installer($dir)->run($options, 'Acme');

    $neon = (new Filesystem)->get($dir.'/phpstan.neon');

    expect($neon)->toContain('level: 9')
        ->and($neon)->toContain('larastan/larastan/extension.neon')
        ->and($neon)->not->toContain('phpstan-strict-rules')
        ->and((new Filesystem)->exists($dir.'/pint.json'))->toBeFalse();
});

it('omits the Stop hook from settings.json when it is not selected', function (): void {
    $dir = temp_project();
    laravelSkeleton($dir);

    $options = new InstallOptions(
        stack: FrontendStack::None,
        pint: true,
        phpstan: false,
        phpstanLevel: 7,
        phpstanStrict: true,
        tests: true,
        testTool: TestTool::Pest,
        coverageMin: 80,
        archTests: false,
        hooks: ['pre-commit'],
        skills: [],
        scaffolding: [],
        force: false,
    );

    installer($dir)->run($options, 'Acme');
    $settings = readJson($dir.'/.claude/settings.json');

    expect($settings)->not->toHaveKey('hooks')
        ->and($settings['permissions']['allow'])->toContain('Bash(vendor/bin/pint:*)')
        ->and($settings['permissions']['allow'])->not->toContain('Bash(vendor/bin/phpstan:*)');
});

it('writes a manifest describing the test configuration', function (): void {
    $dir = temp_project();
    laravelSkeleton($dir);

    $options = new InstallOptions(
        stack: FrontendStack::Blade,
        pint: true,
        phpstan: true,
        phpstanLevel: 6,
        phpstanStrict: true,
        tests: true,
        testTool: TestTool::PHPUnit,
        coverageMin: null,
        archTests: false,
        hooks: ['stop'],
        skills: [],
        scaffolding: [],
        force: false,
    );

    installer($dir)->run($options, 'Acme');
    $manifest = readJson($dir.'/.claude-kit.json');

    expect($manifest['stack'])->toBe('blade')
        ->and($manifest['tests']['enabled'])->toBeTrue()
        ->and($manifest['tests']['tool'])->toBe('phpunit')
        ->and($manifest['tests']['coverage_min'])->toBeNull()
        ->and($manifest['hooks']['feature_docs'])->toBeFalse()
        ->and((new Filesystem)->exists($dir.'/tests/Arch/ArchTest.php'))->toBeFalse();
});

it('skips all frontend files for an API-only project', function (): void {
    $dir = temp_project();
    laravelSkeleton($dir);

    installer($dir)->run(InstallOptions::defaults(FrontendStack::None), 'Acme');

    $files = new Filesystem;
    expect($files->exists($dir.'/eslint.config.js'))->toBeFalse()
        ->and($files->exists($dir.'/package.json'))->toBeFalse()
        ->and($files->exists($dir.'/.claude/skills/laravel-best-practices/SKILL.md'))->toBeTrue();
});

it('installs only prettier scripts for a Blade project', function (): void {
    $dir = temp_project();
    laravelSkeleton($dir);

    installer($dir)->run(InstallOptions::defaults(FrontendStack::Blade), 'Acme');
    $package = readJson($dir.'/package.json');

    expect($package['scripts'])->toHaveKey('format:check')
        ->and($package['scripts'])->not->toHaveKey('types:check')
        ->and((new Filesystem)->exists($dir.'/eslint.config.js'))->toBeFalse();
});

it('does not overwrite existing files without force', function (): void {
    $dir = temp_project();
    laravelSkeleton($dir);
    (new Filesystem)->put($dir.'/CLAUDE.md', 'KEEP ME');

    $report = installer($dir)->run(InstallOptions::defaults(FrontendStack::None), 'Acme');

    expect((new Filesystem)->get($dir.'/CLAUDE.md'))->toBe('KEEP ME')
        ->and($report)->toContain(['action' => 'skipped', 'path' => 'CLAUDE.md']);
});

it('overwrites existing files with force', function (): void {
    $dir = temp_project();
    laravelSkeleton($dir);
    (new Filesystem)->put($dir.'/CLAUDE.md', 'OLD');

    installer($dir)->run(InstallOptions::defaults(FrontendStack::None, force: true), 'Acme');

    expect((new Filesystem)->get($dir.'/CLAUDE.md'))->not->toBe('OLD');
});

it('is idempotent across repeated runs', function (): void {
    $dir = temp_project();
    laravelSkeleton($dir);

    $run = fn (): array => installer($dir)->run(InstallOptions::defaults(FrontendStack::InertiaVue), 'Acme');

    $run();
    $package = (new Filesystem)->get($dir.'/package.json');
    $composer = (new Filesystem)->get($dir.'/composer.json');

    $run();

    expect((new Filesystem)->get($dir.'/package.json'))->toBe($package)
        ->and((new Filesystem)->get($dir.'/composer.json'))->toBe($composer);
});
