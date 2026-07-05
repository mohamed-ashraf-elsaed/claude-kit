<?php

declare(strict_types=1);

use Illuminate\Filesystem\Filesystem;
use MohamedAshrafElsaed\ClaudeKit\Support\ComposerJsonMerger;
use MohamedAshrafElsaed\ClaudeKit\Support\PackageJsonMerger;

it('merges package.json scripts and devDependencies without clobbering existing entries', function (): void {
    $dir = temp_project();
    $path = $dir.'/package.json';
    (new Filesystem)->put($path, json_encode([
        'private' => true,
        'scripts' => ['dev' => 'vite', 'lint:check' => 'my-own-linter'],
        'devDependencies' => ['vite' => '^8.0'],
    ]));

    (new PackageJsonMerger(new Filesystem))->merge(
        $path,
        ['lint:check' => 'eslint .', 'format:check' => 'prettier --check resources/'],
        ['prettier' => '^3.4.2'],
    );

    $json = json_decode((new Filesystem)->get($path), true);

    expect($json['scripts']['lint:check'])->toBe('my-own-linter')
        ->and($json['scripts']['format:check'])->toBe('prettier --check resources/')
        ->and($json['scripts']['dev'])->toBe('vite')
        ->and($json['devDependencies'])->toHaveKeys(['vite', 'prettier']);
});

it('creates a package.json when none exists', function (): void {
    $dir = temp_project();
    $path = $dir.'/package.json';

    (new PackageJsonMerger(new Filesystem))->merge($path, ['format' => 'prettier --write resources/'], ['prettier' => '^3.4.2']);

    expect((new Filesystem)->exists($path))->toBeTrue();

    $json = json_decode((new Filesystem)->get($path), true);

    expect($json['scripts']['format'])->toBe('prettier --write resources/');
});

it('is idempotent for package.json', function (): void {
    $dir = temp_project();
    $path = $dir.'/package.json';
    (new Filesystem)->put($path, json_encode(['scripts' => [], 'devDependencies' => []]));

    $merger = new PackageJsonMerger(new Filesystem);
    $merger->merge($path, ['lint' => 'eslint . --fix'], ['eslint' => '^9.17.0']);
    $first = (new Filesystem)->get($path);
    $merger->merge($path, ['lint' => 'eslint . --fix'], ['eslint' => '^9.17.0']);
    $second = (new Filesystem)->get($path);

    expect($second)->toBe($first);
});

it('appends to composer post-autoload-dump without duplicating', function (): void {
    $dir = temp_project();
    $path = $dir.'/composer.json';
    (new Filesystem)->put($path, json_encode([
        'name' => 'acme/app',
        'scripts' => [
            'post-autoload-dump' => ['@php artisan package:discover --ansi'],
        ],
    ]));

    $merger = new ComposerJsonMerger(new Filesystem);
    $scripts = ['types:check' => ['phpstan analyse']];

    $merger->merge($path, $scripts, ['@hooks:install']);
    $merger->merge($path, $scripts, ['@hooks:install']);

    $json = json_decode((new Filesystem)->get($path), true);

    expect($json['scripts']['types:check'])->toBe(['phpstan analyse'])
        ->and($json['scripts']['post-autoload-dump'])
        ->toBe(['@php artisan package:discover --ansi', '@hooks:install']);
});

it('does not overwrite an existing composer script', function (): void {
    $dir = temp_project();
    $path = $dir.'/composer.json';
    (new Filesystem)->put($path, json_encode([
        'scripts' => ['types:check' => ['my-own-analysis']],
    ]));

    (new ComposerJsonMerger(new Filesystem))->merge($path, ['types:check' => ['phpstan analyse']], []);

    $json = json_decode((new Filesystem)->get($path), true);

    expect($json['scripts']['types:check'])->toBe(['my-own-analysis']);
});
