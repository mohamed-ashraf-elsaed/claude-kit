<?php

declare(strict_types=1);

use Illuminate\Filesystem\Filesystem;
use MohamedAshrafElsaed\ClaudeKit\Support\FileWriter;

it('creates a missing file and records its hash', function (): void {
    $dir = temp_project();
    $writer = new FileWriter(new Filesystem, $dir, force: false);

    $writer->put('.claude/rules/testing.md', 'BODY');

    expect((new Filesystem)->get($dir.'/.claude/rules/testing.md'))->toBe('BODY')
        ->and($writer->report())->toBe([['action' => 'created', 'path' => '.claude/rules/testing.md']])
        ->and($writer->hashes()['.claude/rules/testing.md'])->toBe(hash('sha256', 'BODY'));
});

it('reports an identical file as unchanged without asking anything', function (): void {
    $dir = temp_project();
    (new Filesystem)->put($dir.'/pint.json', 'SAME');

    $resolver = function (): string {
        throw new RuntimeException('the resolver must not be called for identical files');
    };
    $writer = new FileWriter(new Filesystem, $dir, force: false, resolver: $resolver);

    $writer->put('pint.json', 'SAME');

    expect($writer->report())->toBe([['action' => 'unchanged', 'path' => 'pint.json']])
        ->and($writer->hashes())->toHaveKey('pint.json');
});

it('skips a differing file when there is no resolver', function (): void {
    $dir = temp_project();
    (new Filesystem)->put($dir.'/CLAUDE.md', 'MINE');

    $writer = new FileWriter(new Filesystem, $dir, force: false);
    $writer->put('CLAUDE.md', 'THEIRS');

    expect((new Filesystem)->get($dir.'/CLAUDE.md'))->toBe('MINE')
        ->and($writer->report())->toBe([['action' => 'skipped', 'path' => 'CLAUDE.md']])
        ->and($writer->hashes())->toBe([]);
});

it('overwrites a differing file when forced', function (): void {
    $dir = temp_project();
    (new Filesystem)->put($dir.'/CLAUDE.md', 'MINE');

    $writer = new FileWriter(new Filesystem, $dir, force: true);
    $writer->put('CLAUDE.md', 'THEIRS');

    expect((new Filesystem)->get($dir.'/CLAUDE.md'))->toBe('THEIRS')
        ->and($writer->report())->toBe([['action' => 'overwritten', 'path' => 'CLAUDE.md']]);
});

it('asks the resolver for a differing file and honours its answer', function (): void {
    $dir = temp_project();
    $files = new Filesystem;
    $files->put($dir.'/a.md', 'OLD A');
    $files->put($dir.'/b.md', 'OLD B');

    $seen = [];
    $writer = new FileWriter(new Filesystem, $dir, force: false, resolver: function (string $path, string $existing, string $incoming) use (&$seen): string {
        $seen[] = [$path, $existing, $incoming];

        return $path === 'a.md' ? 'overwrite' : 'skip';
    });

    $writer->put('a.md', 'NEW A');
    $writer->put('b.md', 'NEW B');

    expect($files->get($dir.'/a.md'))->toBe('NEW A')
        ->and($files->get($dir.'/b.md'))->toBe('OLD B')
        ->and($seen)->toBe([['a.md', 'OLD A', 'NEW A'], ['b.md', 'OLD B', 'NEW B']])
        ->and($writer->report())->toBe([
            ['action' => 'overwritten', 'path' => 'a.md'],
            ['action' => 'skipped', 'path' => 'b.md'],
        ]);
});

it('refreshes a file still matching the hash it was published with', function (): void {
    $dir = temp_project();
    (new Filesystem)->put($dir.'/rule.md', 'AS PUBLISHED');

    $resolver = function (): string {
        throw new RuntimeException('an untouched file must not be queried');
    };
    $writer = new FileWriter(
        new Filesystem,
        $dir,
        force: false,
        resolver: $resolver,
        publishedHashes: ['rule.md' => hash('sha256', 'AS PUBLISHED')],
    );

    $writer->put('rule.md', 'NEW VERSION');

    expect((new Filesystem)->get($dir.'/rule.md'))->toBe('NEW VERSION')
        ->and($writer->report())->toBe([['action' => 'updated', 'path' => 'rule.md']]);
});

it('asks about a file whose content no longer matches its published hash', function (): void {
    $dir = temp_project();
    (new Filesystem)->put($dir.'/rule.md', 'EDITED BY THE TEAM');

    $asked = false;
    $writer = new FileWriter(
        new Filesystem,
        $dir,
        force: false,
        resolver: function () use (&$asked): string {
            $asked = true;

            return 'skip';
        },
        publishedHashes: ['rule.md' => hash('sha256', 'AS PUBLISHED')],
    );

    $writer->put('rule.md', 'NEW VERSION');

    expect($asked)->toBeTrue()
        ->and((new Filesystem)->get($dir.'/rule.md'))->toBe('EDITED BY THE TEAM');
});

it('writes merged content without consulting the resolver', function (): void {
    $dir = temp_project();
    $files = new Filesystem;
    $files->ensureDirectoryExists($dir.'/.claude');
    $files->put($dir.'/.claude/settings.json', 'OLD');

    $resolver = function (): string {
        throw new RuntimeException('merged writes must never prompt');
    };
    $writer = new FileWriter(new Filesystem, $dir, force: false, resolver: $resolver);

    $writer->putMerged('.claude/settings.json', 'MERGED');

    expect((new Filesystem)->get($dir.'/.claude/settings.json'))->toBe('MERGED')
        ->and($writer->report())->toBe([['action' => 'merged', 'path' => '.claude/settings.json']]);
});

it('reports an unchanged merged file as unchanged', function (): void {
    $dir = temp_project();
    $files = new Filesystem;
    $files->ensureDirectoryExists($dir.'/.claude');
    $files->put($dir.'/.claude/settings.json', 'SAME');

    $writer = new FileWriter(new Filesystem, $dir, force: false);
    $writer->putMerged('.claude/settings.json', 'SAME');

    expect($writer->report())->toBe([['action' => 'unchanged', 'path' => '.claude/settings.json']]);
});

it('makes a published hook executable', function (): void {
    $dir = temp_project();
    $writer = new FileWriter(new Filesystem, $dir, force: false);

    $writer->put('.claude/hooks/phpstan-check.sh', "#!/usr/bin/env bash\n");
    $writer->chmod('.claude/hooks/phpstan-check.sh', 0755);

    expect(is_executable($dir.'/.claude/hooks/phpstan-check.sh'))->toBeTrue();
});

it('diffs two versions of a file line by line', function (): void {
    $diff = FileWriter::diff("keep\nold line\ntail\n", "keep\nnew line\ntail\n");

    expect($diff)->toBe("  keep\n- old line\n+ new line\n  tail");
});

it('diffs pure additions and pure removals', function (): void {
    expect(FileWriter::diff("a\n", "a\nb\n"))->toBe("  a\n+ b")
        ->and(FileWriter::diff("a\nb\n", "a\n"))->toBe("  a\n- b")
        ->and(FileWriter::diff('', "only\n"))->toBe('+ only');
});

it('reports no differences for identical content', function (): void {
    expect(FileWriter::diff("same\nlines\n", "same\nlines\n"))->toBe("  same\n  lines");
});
