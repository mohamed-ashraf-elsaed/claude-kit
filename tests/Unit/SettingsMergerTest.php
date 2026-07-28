<?php

declare(strict_types=1);

use MohamedAshrafElsaed\ClaudeKit\Support\SettingsMerger;

it('adds keys the project does not have', function (): void {
    $merger = new SettingsMerger;

    $merged = $merger->merge(
        ['permissions' => ['allow' => ['Bash(composer:*)']]],
        ['$schema' => 'https://example.test/schema.json'],
    );

    expect($merged['$schema'])->toBe('https://example.test/schema.json')
        ->and($merged['permissions']['allow'])->toBe(['Bash(composer:*)'])
        ->and($merger->summary()['added'])->toBe(['permissions']);
});

it('never clobbers an existing scalar and reports it as kept', function (): void {
    $merger = new SettingsMerger;

    $merged = $merger->merge(
        ['outputStyle' => 'Concise', 'model' => 'opus'],
        ['outputStyle' => 'Explanatory'],
    );

    expect($merged['outputStyle'])->toBe('Explanatory')
        ->and($merged['model'])->toBe('opus')
        ->and($merger->summary()['kept'])->toBe(['outputStyle'])
        ->and($merger->summary()['added'])->toBe(['model']);
});

it('unions lists instead of replacing them', function (): void {
    $merger = new SettingsMerger;

    $merged = $merger->merge(
        ['permissions' => ['allow' => ['Bash(vendor/bin/pint:*)', 'Bash(composer:*)']]],
        ['permissions' => ['allow' => ['Bash(composer:*)', 'Bash(gh:*)']]],
    );

    expect($merged['permissions']['allow'])->toBe([
        'Bash(composer:*)',
        'Bash(gh:*)',
        'Bash(vendor/bin/pint:*)',
    ])->and($merger->summary()['changed'])->toBe(['permissions.allow']);
});

it('deep merges nested maps without dropping sibling keys', function (): void {
    $merger = new SettingsMerger;

    $merged = $merger->merge(
        ['hooks' => ['Stop' => [['hooks' => [['type' => 'command', 'command' => 'ours.sh']]]]]],
        ['hooks' => ['PreToolUse' => [['matcher' => 'Bash', 'hooks' => []]]], 'env' => ['FOO' => 'bar']],
    );

    expect($merged['hooks'])->toHaveKeys(['PreToolUse', 'Stop'])
        ->and($merged['env']['FOO'])->toBe('bar')
        ->and($merger->summary()['added'])->toBe(['hooks.Stop']);
});

it('is idempotent: merging the same settings twice adds no duplicate hook', function (): void {
    $ours = [
        'hooks' => ['Stop' => [['hooks' => [['type' => 'command', 'command' => 'validate.sh']]]]],
        'permissions' => ['allow' => ['Bash(composer:*)']],
    ];

    $first = (new SettingsMerger)->merge($ours, []);
    $merger = new SettingsMerger;
    $second = $merger->merge($ours, $first);

    expect($second)->toBe($first)
        ->and($merger->summary())->toBe(['added' => [], 'changed' => [], 'kept' => []]);
});

it('keeps the project value when our type does not match theirs', function (): void {
    $merger = new SettingsMerger;

    $merged = $merger->merge(['permissions' => ['allow' => ['a']]], ['permissions' => 'inherit']);

    expect($merged['permissions'])->toBe('inherit')
        ->and($merger->summary()['kept'])->toBe(['permissions']);
});
