<?php

declare(strict_types=1);

use Illuminate\Filesystem\Filesystem;
use MohamedAshrafElsaed\ClaudeKit\Support\CleanCodePreset;

it('maps every preset to a label and an existing rule stub', function (): void {
    $files = new Filesystem;

    foreach (CleanCodePreset::cases() as $preset) {
        expect($preset->label())->toContain($preset->value)
            ->and($preset->stub())->toBe('claude/rules/clean-code.'.$preset->value.'.md')
            ->and($files->exists(stubs_path().'/'.$preset->stub()))->toBeTrue();
    }
});
