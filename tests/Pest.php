<?php

declare(strict_types=1);

use Illuminate\Filesystem\Filesystem;
use MohamedAshrafElsaed\ClaudeKit\Tests\TestCase;

uses(TestCase::class)->in('Feature');

/**
 * @var list<string> $__claudeKitTempDirs
 */
$GLOBALS['__claudeKitTempDirs'] = [];

afterEach(function (): void {
    $files = new Filesystem;

    foreach ($GLOBALS['__claudeKitTempDirs'] as $path) {
        if ($files->isDirectory($path)) {
            $files->deleteDirectory($path);
        }
    }

    $GLOBALS['__claudeKitTempDirs'] = [];
});

/**
 * Absolute path to the package's stubs directory.
 */
function stubs_path(): string
{
    return dirname(__DIR__).'/stubs';
}

/**
 * Create a fresh, isolated temp directory and register it for cleanup after the
 * test.
 */
function temp_project(): string
{
    $path = sys_get_temp_dir().'/claude-kit-'.bin2hex(random_bytes(6));
    (new Filesystem)->ensureDirectoryExists($path);
    $GLOBALS['__claudeKitTempDirs'][] = $path;

    return $path;
}
