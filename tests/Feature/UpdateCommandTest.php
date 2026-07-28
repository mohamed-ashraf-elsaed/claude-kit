<?php

declare(strict_types=1);

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Artisan;

/**
 * A project that has already been installed once, non-interactively, so an
 * update has a manifest and a set of published hashes to work from.
 */
function installedProject(string $stack = 'none'): string
{
    $dir = temp_project();
    $files = new Filesystem;
    $files->ensureDirectoryExists($dir.'/app');
    $files->put($dir.'/composer.json', json_encode(['name' => 'acme/app', 'scripts' => []]));

    app()->setBasePath($dir);
    Artisan::call('claude-kit:install', ['--stack' => $stack, '--no-interaction' => true]);

    return $dir;
}

it('fails when the project has never been installed', function (): void {
    $dir = temp_project();
    $this->app->setBasePath($dir);

    $this->artisan('claude-kit:update')
        ->expectsOutputToContain('run `php artisan claude-kit:install` first')
        ->assertFailed();
});

it('fails when the manifest records an unknown stack', function (): void {
    $dir = installedProject();
    $files = new Filesystem;
    $manifest = json_decode($files->get($dir.'/.claude/claude-kit.json'), true);
    $manifest['stack'] = 'svelte';
    $files->put($dir.'/.claude/claude-kit.json', json_encode($manifest));

    $this->artisan('claude-kit:update')
        ->expectsOutputToContain('unknown frontend stack')
        ->assertFailed();
});

it('re-publishes the selections recorded at install time', function (): void {
    $dir = installedProject();
    $files = new Filesystem;

    // Simulate the file being lost, and one being edited by the team.
    $files->delete($dir.'/.claude/rules/clean-code.md');
    $files->put($dir.'/pint.json', '{"preset":"mine"}');

    $this->artisan('claude-kit:update', ['--no-interaction' => true])->assertSuccessful();

    expect($files->exists($dir.'/.claude/rules/clean-code.md'))->toBeTrue()
        ->and($files->get($dir.'/pint.json'))->toBe('{"preset":"mine"}');
});

it('refreshes a file the project never touched without asking', function (): void {
    $dir = installedProject();
    $files = new Filesystem;

    // A newer claude-kit ships different content for a file still matching the
    // hash recorded at install time, so there is no edit to lose.
    $recorded = json_decode($files->get($dir.'/.claude/claude-kit.json'), true);
    expect($recorded['files'])->toHaveKey('.claude/rules/clean-code.md');

    $recorded['files']['.claude/rules/clean-code.md'] = hash('sha256', 'PRETEND OLD VERSION');
    $files->put($dir.'/.claude/claude-kit.json', json_encode($recorded));
    $files->put($dir.'/.claude/rules/clean-code.md', 'PRETEND OLD VERSION');

    // --no-interaction: nothing can be asked, so only the hash rule can refresh it.
    $this->artisan('claude-kit:update', ['--no-interaction' => true])->assertSuccessful();

    expect($files->get($dir.'/.claude/rules/clean-code.md'))->not->toBe('PRETEND OLD VERSION')
        ->and($files->get($dir.'/.claude/rules/clean-code.md'))->toContain('Clean code');
});

it('keeps an edited file when the answer is to keep it', function (): void {
    $dir = installedProject();
    $files = new Filesystem;
    $files->put($dir.'/.claude/rules/anti-assumption.md', 'OUR OWN VERSION');

    $this->artisan('claude-kit:update')
        ->expectsChoice('.claude/rules/anti-assumption.md exists and differs from the claude-kit version.', 'skip', [
            'skip' => 'Keep mine',
            'overwrite' => 'Replace it',
            'diff' => 'Show me the difference first',
            'skip-all' => 'Keep mine — and for every remaining file',
            'overwrite-all' => 'Replace it — and every remaining file',
        ])
        ->assertSuccessful();

    expect($files->get($dir.'/.claude/rules/anti-assumption.md'))->toBe('OUR OWN VERSION');
});

it('replaces an edited file when the answer is to replace it', function (): void {
    $dir = installedProject();
    $files = new Filesystem;
    $files->put($dir.'/.claude/rules/anti-assumption.md', 'OUR OWN VERSION');

    $this->artisan('claude-kit:update')
        ->expectsChoice('.claude/rules/anti-assumption.md exists and differs from the claude-kit version.', 'overwrite', [
            'skip' => 'Keep mine',
            'overwrite' => 'Replace it',
            'diff' => 'Show me the difference first',
            'skip-all' => 'Keep mine — and for every remaining file',
            'overwrite-all' => 'Replace it — and every remaining file',
        ])
        ->assertSuccessful();

    expect($files->get($dir.'/.claude/rules/anti-assumption.md'))->toContain('Verify, or ask');
});

it('replaces every edited file without asking when forced', function (): void {
    $dir = installedProject();
    $files = new Filesystem;
    $files->put($dir.'/.claude/rules/anti-assumption.md', 'OUR OWN VERSION');
    $files->put($dir.'/pint.json', 'OURS');

    $this->artisan('claude-kit:update', ['--force' => true, '--no-interaction' => true])->assertSuccessful();

    expect($files->get($dir.'/.claude/rules/anti-assumption.md'))->not->toBe('OUR OWN VERSION')
        ->and($files->get($dir.'/pint.json'))->not->toBe('OURS');
});

it('does not resurrect anything the install declined', function (): void {
    $dir = installedProject();
    $files = new Filesystem;
    $manifest = json_decode($files->get($dir.'/.claude/claude-kit.json'), true);
    $manifest['architecture'] = ['services'];
    $manifest['agents'] = [];
    $manifest['rules']['worktrees'] = false;
    $manifest['output_style'] = false;
    $files->put($dir.'/.claude/claude-kit.json', json_encode($manifest));
    $files->delete($dir.'/.claude/rules/architecture-actions.md');
    $files->deleteDirectory($dir.'/.claude/agents');
    $files->delete($dir.'/.claude/rules/worktrees.md');

    $this->artisan('claude-kit:update', ['--no-interaction' => true])->assertSuccessful();

    expect($files->exists($dir.'/.claude/rules/architecture-services.md'))->toBeTrue()
        ->and($files->exists($dir.'/.claude/rules/architecture-actions.md'))->toBeFalse()
        ->and($files->exists($dir.'/.claude/agents/design-reviewer.md'))->toBeFalse()
        ->and($files->exists($dir.'/.claude/rules/worktrees.md'))->toBeFalse();
});

it('reports how many files changed and how many were already current', function (): void {
    $dir = installedProject();

    $this->artisan('claude-kit:update', ['--no-interaction' => true])
        ->expectsOutputToContain('already current')
        ->assertSuccessful();
});
