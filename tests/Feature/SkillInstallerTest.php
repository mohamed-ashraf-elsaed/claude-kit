<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Process;
use MohamedAshrafElsaed\ClaudeKit\Support\SkillInstaller;

it('reports availability from npx --version', function (): void {
    Process::fake(['npx --version' => Process::result('10.9.0')]);

    expect((new SkillInstaller(sys_get_temp_dir()))->isAvailable())->toBeTrue();

    Process::assertRan('npx --version');
});

it('reports unavailable when npx fails', function (): void {
    Process::fake(['npx --version' => Process::result(exitCode: 1)]);

    expect((new SkillInstaller(sys_get_temp_dir()))->isAvailable())->toBeFalse();
});

it('returns the finder output for a query', function (): void {
    Process::fake(['*' => Process::result('found: acme/skill')]);

    expect((new SkillInstaller(sys_get_temp_dir()))->find('acme'))->toContain('found: acme/skill');
});

it('adds a skill package', function (): void {
    Process::fake(['*' => Process::result('installed')]);

    expect((new SkillInstaller(sys_get_temp_dir()))->add('vercel-labs/skills'))->toBeTrue();
});
