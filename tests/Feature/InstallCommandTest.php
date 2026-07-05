<?php

declare(strict_types=1);

use Illuminate\Filesystem\Filesystem;

it('scaffolds files into the project when run', function (): void {
    $dir = temp_project();
    $files = new Filesystem;
    $files->ensureDirectoryExists($dir.'/app');
    $files->put($dir.'/composer.json', json_encode(['name' => 'acme/app', 'scripts' => []]));

    $this->app->setBasePath($dir);

    $this->artisan('claude-kit:install', [
        '--stack' => 'inertia-vue',
        '--parts' => 'claude,rules,quality',
    ])->assertSuccessful();

    expect($files->exists($dir.'/.claude/settings.json'))->toBeTrue()
        ->and($files->exists($dir.'/CLAUDE.md'))->toBeTrue()
        ->and($files->exists($dir.'/phpstan.neon'))->toBeTrue();
});

it('fails on an unknown stack', function (): void {
    $dir = temp_project();
    $this->app->setBasePath($dir);

    $this->artisan('claude-kit:install', ['--stack' => 'svelte'])
        ->assertFailed();
});
