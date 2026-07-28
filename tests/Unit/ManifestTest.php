<?php

declare(strict_types=1);

use Illuminate\Filesystem\Filesystem;
use MohamedAshrafElsaed\ClaudeKit\Support\Manifest;
use MohamedAshrafElsaed\ClaudeKit\Support\StubRenderer;

it('writes the manifest to .claude/claude-kit.json', function (): void {
    $dir = temp_project();
    $manifest = new Manifest(new Filesystem, $dir);

    expect($manifest->write(['stack' => 'livewire']))->toBe('created')
        ->and($manifest->write(['stack' => 'livewire']))->toBe('overwritten')
        ->and((new Filesystem)->exists($dir.'/.claude/claude-kit.json'))->toBeTrue()
        ->and($manifest->read()['stack'])->toBe('livewire');
});

it('falls back to the legacy root manifest when reading', function (): void {
    $dir = temp_project();
    (new Filesystem)->put($dir.'/.claude-kit.json', json_encode(['stack' => 'blade']));

    $manifest = new Manifest(new Filesystem, $dir);

    expect($manifest->exists())->toBeTrue()
        ->and($manifest->read()['stack'])->toBe('blade');
});

it('prefers the current manifest over the legacy one', function (): void {
    $dir = temp_project();
    $files = new Filesystem;
    $files->put($dir.'/.claude-kit.json', json_encode(['stack' => 'blade']));
    $files->ensureDirectoryExists($dir.'/.claude');
    $files->put($dir.'/.claude/claude-kit.json', json_encode(['stack' => 'none']));

    expect((new Manifest(new Filesystem, $dir))->read()['stack'])->toBe('none');
});

it('returns an empty manifest when there is none or it is invalid', function (): void {
    $dir = temp_project();
    $manifest = new Manifest(new Filesystem, $dir);

    expect($manifest->exists())->toBeFalse()
        ->and($manifest->read())->toBe([])
        ->and($manifest->publishedHashes())->toBe([]);

    (new Filesystem)->put($dir.'/.claude-kit.json', 'not json at all');

    expect($manifest->read())->toBe([]);
});

it('exposes the recorded file hashes and ignores malformed entries', function (): void {
    $dir = temp_project();
    $manifest = new Manifest(new Filesystem, $dir);
    $manifest->write(['files' => ['CLAUDE.md' => 'abc123', 'bad' => ['not', 'a', 'hash']]]);

    expect($manifest->publishedHashes())->toBe(['CLAUDE.md' => 'abc123']);
});

it('renders a stub with placeholder replacements', function (): void {
    $dir = temp_project();
    (new Filesystem)->put($dir.'/greeting.md.stub', 'Hello {{NAME}}, welcome to {{NAME}}.');

    $renderer = new StubRenderer(new Filesystem, $dir);

    expect($renderer->render('greeting.md.stub', ['{{NAME}}' => 'Acme']))->toBe('Hello Acme, welcome to Acme.')
        ->and($renderer->render('greeting.md.stub'))->toBe('Hello {{NAME}}, welcome to {{NAME}}.')
        ->and($renderer->exists('greeting.md.stub'))->toBeTrue()
        ->and($renderer->exists('missing.stub'))->toBeFalse()
        ->and($renderer->path('greeting.md.stub'))->toBe($dir.'/greeting.md.stub');
});
