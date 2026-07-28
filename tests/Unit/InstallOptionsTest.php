<?php

declare(strict_types=1);

use MohamedAshrafElsaed\ClaudeKit\Support\CleanCodePreset;
use MohamedAshrafElsaed\ClaudeKit\Support\FrontendStack;
use MohamedAshrafElsaed\ClaudeKit\Support\InstallOptions;
use MohamedAshrafElsaed\ClaudeKit\Support\TestTool;

it('turns everything on by default', function (): void {
    $options = InstallOptions::defaults(FrontendStack::Livewire);

    expect($options->phpstanLevel)->toBe(7)
        ->and($options->phpstanStrict)->toBeTrue()
        ->and($options->phpstanHook)->toBe('block')
        ->and($options->installsPhpstanHook())->toBeTrue()
        ->and($options->cleanCode)->toBe(CleanCodePreset::Balanced)
        ->and($options->minPasses)->toBe(2)
        ->and($options->deployCommand)->toBe('')
        ->and($options->commitConvention)->toBeNull()
        ->and($options->architecture)->toBe(InstallOptions::LAYERS)
        ->and($options->validationChecks)->toBe(InstallOptions::CHECKS)
        ->and($options->skills)->toBe(FrontendStack::Livewire->skills());
});

it('computes the dev packages the defaults need', function (): void {
    expect(InstallOptions::defaults(FrontendStack::None)->requiredDevPackages())->toBe([
        'laravel/pint',
        'larastan/larastan',
        'phpstan/phpstan-strict-rules',
        'pestphp/pest',
        'pestphp/pest-plugin-laravel',
    ]);
});

it('leaves out the packages for every declined tool', function (): void {
    $none = InstallOptions::fromManifest([
        'pint' => false,
        'phpstan' => ['enabled' => false],
        'tests' => ['enabled' => false],
    ], FrontendStack::None);

    expect($none->requiredDevPackages())->toBe([]);
});

it('asks for phpstan itself when larastan is declined', function (): void {
    $options = InstallOptions::fromManifest([
        'pint' => false,
        'phpstan' => ['enabled' => true, 'larastan' => false, 'strict' => false],
        'tests' => ['enabled' => false],
    ], FrontendStack::None);

    expect($options->requiredDevPackages())->toBe(['phpstan/phpstan']);
});

it('asks for phpunit rather than pest when phpunit was chosen', function (): void {
    $options = InstallOptions::fromManifest([
        'pint' => false,
        'phpstan' => ['enabled' => false],
        'tests' => ['enabled' => true, 'tool' => 'phpunit'],
    ], FrontendStack::None);

    expect($options->requiredDevPackages())->toBe(['phpunit/phpunit'])
        ->and($options->requiredDevPackages())->not->toContain('pestphp/pest');
});

it('does not enforce coverage by default', function (): void {
    expect(InstallOptions::defaults(FrontendStack::None)->coverageMin)->toBeNull();
});

it('never runs architecture tests under phpunit', function (): void {
    $options = InstallOptions::fromManifest([
        'tests' => ['enabled' => true, 'tool' => 'phpunit', 'arch' => true],
    ], FrontendStack::None);

    expect($options->archTests)->toBeTrue()
        ->and($options->testTool)->toBe(TestTool::PHPUnit)
        ->and($options->runsArchitectureTests())->toBeFalse();
});

it('answers membership questions for every selection group', function (): void {
    $options = InstallOptions::defaults(FrontendStack::None);

    expect($options->hasLayer('value-objects'))->toBeTrue()
        ->and($options->hasLayer('nope'))->toBeFalse()
        ->and($options->hasAgent('docs-reviewer'))->toBeTrue()
        ->and($options->hasWorkflowSkill('codemap'))->toBeTrue()
        ->and($options->hasBehavioralRule('anti-assumption'))->toBeTrue()
        ->and($options->hasHook('stop'))->toBeTrue()
        ->and($options->wants('ci'))->toBeTrue()
        ->and($options->runsArchitectureTests())->toBeTrue();
});

it('reports no phpstan hook when phpstan is off or the hook was declined', function (): void {
    $base = InstallOptions::defaults(FrontendStack::None);

    $hookOff = new InstallOptions(
        stack: $base->stack, pint: $base->pint, phpstan: true, phpstanLevel: 7,
        phpstanStrict: true, phpstanLarastan: true, phpstanHook: 'off', tests: $base->tests,
        testTool: $base->testTool, coverageMin: $base->coverageMin, archTests: $base->archTests,
        architecture: $base->architecture, cleanCode: $base->cleanCode, hooks: $base->hooks,
        validationChecks: $base->validationChecks, minPasses: $base->minPasses, agents: $base->agents,
        workflowSkills: $base->workflowSkills, deployCommand: '', commitConvention: null,
        behavioralRules: $base->behavioralRules, outputStyle: false, worktreeRule: false,
        skills: [], scaffolding: [], force: false,
    );

    expect($hookOff->installsPhpstanHook())->toBeFalse();
});

it('rebuilds the selections from a manifest', function (): void {
    $manifest = [
        'stack' => 'blade',
        'pint' => false,
        'phpstan' => ['enabled' => true, 'level' => 9, 'strict' => false, 'larastan' => false, 'hook' => 'warn'],
        'tests' => ['enabled' => true, 'tool' => 'phpunit', 'coverage_min' => 65, 'arch' => false],
        'architecture' => ['services', 'contracts'],
        'rules' => ['clean_code' => 'strict', 'behavioral' => ['anti-assumption'], 'worktrees' => false],
        'hooks' => ['enabled' => ['stop'], 'checks' => ['tests'], 'min_passes' => 5, 'feature_docs' => false],
        'agents' => ['design-reviewer'],
        'skills' => [
            'bundled' => ['pest-testing'],
            'workflow' => ['commit'],
            'deploy_command' => 'php artisan deploy',
            'commit_convention' => 'Ticket-first: [ABC-123] summary',
        ],
        'output_style' => false,
        'scaffolding' => ['rules'],
    ];

    $options = InstallOptions::fromManifest($manifest, FrontendStack::Blade);

    expect($options->pint)->toBeFalse()
        ->and($options->phpstanLevel)->toBe(9)
        ->and($options->phpstanStrict)->toBeFalse()
        ->and($options->phpstanLarastan)->toBeFalse()
        ->and($options->phpstanHook)->toBe('warn')
        ->and($options->testTool)->toBe(TestTool::PHPUnit)
        ->and($options->coverageMin)->toBe(65)
        ->and($options->archTests)->toBeFalse()
        ->and($options->architecture)->toBe(['services', 'contracts'])
        ->and($options->cleanCode)->toBe(CleanCodePreset::Strict)
        ->and($options->hooks)->toBe(['stop'])
        ->and($options->validationChecks)->toBe(['tests'])
        ->and($options->minPasses)->toBe(5)
        ->and($options->agents)->toBe(['design-reviewer'])
        ->and($options->workflowSkills)->toBe(['commit'])
        ->and($options->deployCommand)->toBe('php artisan deploy')
        ->and($options->commitConvention)->toBe('Ticket-first: [ABC-123] summary')
        ->and($options->behavioralRules)->toBe(['anti-assumption'])
        ->and($options->worktreeRule)->toBeFalse()
        ->and($options->outputStyle)->toBeFalse()
        ->and($options->skills)->toBe(['pest-testing'])
        ->and($options->scaffolding)->toBe(['rules']);
});

it('falls back to defaults for keys an older manifest does not have', function (): void {
    $options = InstallOptions::fromManifest(
        ['stack' => 'none', 'tests' => ['enabled' => true, 'tool' => 'pest', 'coverage_min' => null]],
        FrontendStack::None,
    );

    $defaults = InstallOptions::defaults(FrontendStack::None);

    expect($options->coverageMin)->toBeNull()
        ->and($options->phpstanLevel)->toBe($defaults->phpstanLevel)
        ->and($options->cleanCode)->toBe($defaults->cleanCode)
        ->and($options->minPasses)->toBe($defaults->minPasses)
        ->and($options->architecture)->toBe($defaults->architecture)
        ->and($options->agents)->toBe($defaults->agents);
});

it('ignores malformed manifest values', function (): void {
    $options = InstallOptions::fromManifest([
        'phpstan' => 'yes please',
        'architecture' => ['services', 42, null],
        'tests' => ['coverage_min' => 'eighty'],
        'skills' => ['commit_convention' => ['not', 'a', 'string']],
    ], FrontendStack::None);

    expect($options->phpstanLevel)->toBe(7)
        ->and($options->architecture)->toBe(['services'])
        ->and($options->coverageMin)->toBeNull()
        ->and($options->commitConvention)->toBeNull();
});
