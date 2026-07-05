<?php

declare(strict_types=1);

use Illuminate\Filesystem\Filesystem;
use MohamedAshrafElsaed\ClaudeKit\Support\FrontendStack;
use MohamedAshrafElsaed\ClaudeKit\Support\Installer;
use MohamedAshrafElsaed\ClaudeKit\Support\Part;

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

it('installs the full kit for an Inertia + Vue project', function (): void {
    $dir = temp_project();
    laravelSkeleton($dir);

    installer($dir)->run(Part::values(), FrontendStack::InertiaVue, false, 'Acme');

    $files = new Filesystem;
    expect($files->exists($dir.'/.claude/settings.json'))->toBeTrue()
        ->and($files->exists($dir.'/.mcp.json'))->toBeTrue()
        ->and($files->exists($dir.'/.claude/skills/inertia-vue-development/SKILL.md'))->toBeTrue()
        ->and($files->exists($dir.'/.claude/skills/wayfinder-development/SKILL.md'))->toBeTrue()
        ->and($files->exists($dir.'/CLAUDE.md'))->toBeTrue()
        ->and($files->exists($dir.'/phpstan.neon'))->toBeTrue()
        ->and($files->exists($dir.'/pint.json'))->toBeTrue()
        ->and($files->exists($dir.'/tests/Arch/ArchTest.php'))->toBeTrue()
        ->and($files->exists($dir.'/.githooks/pre-commit'))->toBeTrue()
        ->and($files->exists($dir.'/eslint.config.js'))->toBeTrue()
        ->and($files->exists($dir.'/tsconfig.json'))->toBeTrue()
        ->and($files->exists($dir.'/package.json'))->toBeTrue()
        ->and($files->exists($dir.'/features/_TEMPLATE/FEATURE.md'))->toBeTrue()
        ->and($files->exists($dir.'/.github/workflows/tests.yml'))->toBeTrue();
});

it('renders CLAUDE.md placeholders for the chosen stack', function (): void {
    $dir = temp_project();
    laravelSkeleton($dir);

    installer($dir)->run([Part::Rules->value], FrontendStack::InertiaVue, false, 'Acme');

    $claude = (new Filesystem)->get($dir.'/CLAUDE.md');

    expect($claude)->toContain('# Acme Engineering Rules')
        ->and($claude)->toContain('Vue 3 + Inertia')
        ->and($claude)->not->toContain('{{PROJECT_NAME}}')
        ->and($claude)->not->toContain('{{FRONTEND_RULES}}');
});

it('skips all frontend files for an API-only project', function (): void {
    $dir = temp_project();
    laravelSkeleton($dir);

    installer($dir)->run(Part::values(), FrontendStack::None, false, 'Acme');

    $files = new Filesystem;
    expect($files->exists($dir.'/eslint.config.js'))->toBeFalse()
        ->and($files->exists($dir.'/tsconfig.json'))->toBeFalse()
        ->and($files->exists($dir.'/package.json'))->toBeFalse()
        ->and($files->exists($dir.'/.claude/skills/laravel-best-practices/SKILL.md'))->toBeTrue();
});

it('installs only prettier scripts for a Blade project', function (): void {
    $dir = temp_project();
    laravelSkeleton($dir);

    installer($dir)->run([Part::Frontend->value], FrontendStack::Blade, false, 'Acme');

    $package = json_decode((new Filesystem)->get($dir.'/package.json'), true);

    expect($package['scripts'])->toHaveKey('format:check')
        ->and($package['scripts'])->not->toHaveKey('types:check')
        ->and((new Filesystem)->exists($dir.'/eslint.config.js'))->toBeFalse();
});

it('does not overwrite existing files without --force', function (): void {
    $dir = temp_project();
    laravelSkeleton($dir);
    (new Filesystem)->put($dir.'/CLAUDE.md', 'KEEP ME');

    $report = installer($dir)->run([Part::Rules->value], FrontendStack::None, false, 'Acme');

    expect((new Filesystem)->get($dir.'/CLAUDE.md'))->toBe('KEEP ME')
        ->and($report)->toContain(['action' => 'skipped', 'path' => 'CLAUDE.md']);
});

it('overwrites existing files with --force', function (): void {
    $dir = temp_project();
    laravelSkeleton($dir);
    (new Filesystem)->put($dir.'/CLAUDE.md', 'OLD');

    installer($dir)->run([Part::Rules->value], FrontendStack::None, true, 'Acme');

    expect((new Filesystem)->get($dir.'/CLAUDE.md'))->not->toBe('OLD');
});

it('is idempotent across repeated runs', function (): void {
    $dir = temp_project();
    laravelSkeleton($dir);

    $run = fn (): array => installer($dir)->run(Part::values(), FrontendStack::InertiaVue, false, 'Acme');

    $run();
    $package = (new Filesystem)->get($dir.'/package.json');
    $composer = (new Filesystem)->get($dir.'/composer.json');

    $run();

    expect((new Filesystem)->get($dir.'/package.json'))->toBe($package)
        ->and((new Filesystem)->get($dir.'/composer.json'))->toBe($composer);
});
