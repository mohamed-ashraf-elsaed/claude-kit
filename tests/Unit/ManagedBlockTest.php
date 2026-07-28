<?php

declare(strict_types=1);

use MohamedAshrafElsaed\ClaudeKit\Support\ManagedBlock;

it('wraps a body in begin and end markers', function (): void {
    $wrapped = ManagedBlock::wrap("# Rules\n");

    expect($wrapped)->toBe("<!-- claude-kit:begin -->\n# Rules\n<!-- claude-kit:end -->\n");
});

it('wraps the body with its heading intact when there is no file yet', function (): void {
    expect(ManagedBlock::applyTo('', "# Shop\n\nBody.\n"))
        ->toBe("<!-- claude-kit:begin -->\n# Shop\n\nBody.\n<!-- claude-kit:end -->\n")
        ->and(ManagedBlock::applyTo("\n  \n", "# Shop\n\nBody.\n"))
        ->toContain('# Shop');
});

it('appends a managed block to a file that has none, dropping the block heading', function (): void {
    $result = ManagedBlock::applyTo("# My project\n\nHand-written notes.\n", "# My project\n\nGenerated.\n");

    expect($result)->toBe("# My project\n\nHand-written notes.\n\n<!-- claude-kit:begin -->\nGenerated.\n<!-- claude-kit:end -->\n")
        ->and(substr_count($result, "\n# "))->toBe(0)
        ->and(substr_count($result, '# My project'))->toBe(1);
});

it('keeps the block heading when the file holds nothing but the block', function (): void {
    $once = ManagedBlock::applyTo('', "# Shop\n\nBody.\n");
    $again = ManagedBlock::applyTo($once, "# Shop\n\nBody two.\n");

    expect($again)->toContain('# Shop')
        ->and($again)->toContain('Body two.');
});

it('drops the block heading when replacing a block in a file with its own content', function (): void {
    $existing = "# Shop\n\nOurs.\n\n<!-- claude-kit:begin -->\nOld.\n<!-- claude-kit:end -->\n";

    $result = ManagedBlock::applyTo($existing, "# Shop\n\nNew.\n");

    expect($result)->toContain('Ours.')
        ->and($result)->toContain('New.')
        ->and(substr_count($result, '# Shop'))->toBe(1);
});

it('leaves a body that has no heading alone', function (): void {
    expect(ManagedBlock::applyTo("# Mine\n\nNotes.\n", "## Section\n\nBody.\n"))
        ->toContain("<!-- claude-kit:begin -->\n## Section");
});

it('replaces only the managed block and keeps the text around it', function (): void {
    $existing = "Before the block.\n\n<!-- claude-kit:begin -->\nOLD\n<!-- claude-kit:end -->\n\nAfter the block.\n";

    $result = ManagedBlock::applyTo($existing, 'NEW');

    expect($result)->toContain('Before the block.')
        ->and($result)->toContain('After the block.')
        ->and($result)->toContain('NEW')
        ->and($result)->not->toContain('OLD');
});

it('is idempotent when applied twice with the same body', function (): void {
    $once = ManagedBlock::applyTo("# Project\n", 'BODY');
    $twice = ManagedBlock::applyTo($once, 'BODY');

    expect($twice)->toBe($once);
});
