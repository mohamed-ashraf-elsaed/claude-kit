<?php

declare(strict_types=1);

use Illuminate\Filesystem\Filesystem;

/**
 * Runs the shipped gate against a throwaway project directory and returns its
 * exit code plus whatever it wrote to stderr.
 *
 * @param  array<string, mixed>  $manifest
 * @return array{exit: int, stderr: string}
 */
function runQualityChecks(array $manifest): array
{
    $dir = temp_project();
    $files = new Filesystem;
    $files->ensureDirectoryExists($dir.'/.claude');
    $files->put($dir.'/.claude/claude-kit.json', json_encode($manifest));

    $script = dirname(__DIR__, 2).'/runtime/quality-checks.sh';
    $stderr = $dir.'/stderr.log';
    $exit = 0;

    exec(
        'cd '.escapeshellarg($dir).' && bash '.escapeshellarg($script).' 2>'.escapeshellarg($stderr),
        $ignored,
        $exit,
    );

    return ['exit' => $exit, 'stderr' => $files->exists($stderr) ? $files->get($stderr) : ''];
}

it('warns when the manifest expects a check whose binary is missing', function (): void {
    $result = runQualityChecks([
        'phpstan' => ['enabled' => true, 'larastan' => true, 'strict' => true],
        'tests' => ['enabled' => true, 'tool' => 'pest', 'coverage_min' => null],
        'hooks' => ['checks' => ['pint', 'phpstan', 'tests']],
    ]);

    expect($result['stderr'])
        ->toContain("WARNING: manifest expects 'pint' but vendor/bin/pint is missing — check skipped. Run: composer require --dev laravel/pint")
        ->toContain("WARNING: manifest expects 'phpstan' but vendor/bin/phpstan is missing — check skipped. Run: composer require --dev larastan/larastan phpstan/phpstan-strict-rules")
        ->toContain("WARNING: manifest expects 'tests' but vendor/bin/pest is missing — check skipped. Run: composer require --dev pestphp/pest pestphp/pest-plugin-laravel")
        // Warning only: a skipped check must not change the outcome.
        ->and($result['exit'])->toBe(0);
});

it('stays silent about a check the manifest does not ask for', function (): void {
    $result = runQualityChecks([
        'phpstan' => ['enabled' => true],
        'tests' => ['enabled' => true, 'tool' => 'pest', 'coverage_min' => null],
        'hooks' => ['checks' => ['pint']],
    ]);

    expect($result['stderr'])->toContain("manifest expects 'pint'")
        ->and($result['stderr'])->not->toContain("manifest expects 'phpstan'")
        ->and($result['stderr'])->not->toContain("manifest expects 'tests'")
        ->and($result['exit'])->toBe(0);
});

it('names phpstan itself when the manifest declined larastan', function (): void {
    $result = runQualityChecks([
        'phpstan' => ['enabled' => true, 'larastan' => false, 'strict' => false],
        'hooks' => ['checks' => ['phpstan']],
    ]);

    expect($result['stderr'])->toContain('Run: composer require --dev phpstan/phpstan')
        ->and($result['stderr'])->not->toContain('larastan/larastan')
        ->and($result['stderr'])->not->toContain('phpstan-strict-rules');
});

it('names the phpunit package when the manifest chose phpunit', function (): void {
    $result = runQualityChecks([
        'tests' => ['enabled' => true, 'tool' => 'phpunit', 'coverage_min' => null],
        'hooks' => ['checks' => ['tests']],
    ]);

    expect($result['stderr'])
        ->toContain("manifest expects 'tests' but vendor/bin/phpunit is missing")
        ->toContain('Run: composer require --dev phpunit/phpunit');
});

it('says nothing when the manifest records no checks at all', function (): void {
    $result = runQualityChecks(['tests' => ['enabled' => false]]);

    expect($result['stderr'])->not->toContain('WARNING: manifest expects')
        ->and($result['exit'])->toBe(0);
});
