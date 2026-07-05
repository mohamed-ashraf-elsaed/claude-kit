<?php

declare(strict_types=1);

use MohamedAshrafElsaed\ClaudeKit\Support\TestTool;

it('exposes a label, binary and architecture support per tool', function (): void {
    expect(TestTool::Pest->label())->toBe('Pest')
        ->and(TestTool::Pest->binary())->toBe('vendor/bin/pest')
        ->and(TestTool::Pest->supportsArchitectureTests())->toBeTrue()
        ->and(TestTool::PHPUnit->label())->toBe('PHPUnit')
        ->and(TestTool::PHPUnit->binary())->toBe('vendor/bin/phpunit')
        ->and(TestTool::PHPUnit->supportsArchitectureTests())->toBeFalse();
});
