<?php

declare(strict_types=1);

use MohamedAshrafElsaed\ClaudeKit\Support\PhpunitXmlPatcher;

function laravelPhpunitXml(): string
{
    return <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" bootstrap="vendor/autoload.php" colors="true">
    <testsuites>
        <testsuite name="Unit">
            <directory>tests/Unit</directory>
        </testsuite>
        <testsuite name="Feature">
            <directory>tests/Feature</directory>
        </testsuite>
    </testsuites>
    <source>
        <include>
            <directory>app</directory>
        </include>
    </source>
</phpunit>

XML;
}

it('adds a tests/Arch testsuite to a stock Laravel phpunit.xml', function (): void {
    $result = (new PhpunitXmlPatcher)->addTestsuite(laravelPhpunitXml(), 'Arch', 'tests/Arch');

    expect($result['status'])->toBe('added')
        ->and($result['xml'])->toContain('<testsuite name="Arch">')
        ->and($result['xml'])->toContain('<directory>tests/Arch</directory>')
        // everything that was there before survives
        ->and($result['xml'])->toContain('<testsuite name="Unit">')
        ->and($result['xml'])->toContain('<testsuite name="Feature">')
        ->and($result['xml'])->toContain('bootstrap="vendor/autoload.php"')
        ->and($result['xml'])->toContain('<directory>app</directory>');
});

it('indents the new testsuite to match the file', function (): void {
    $result = (new PhpunitXmlPatcher)->addTestsuite(laravelPhpunitXml(), 'Arch', 'tests/Arch');

    expect($result['xml'])->toContain("        <testsuite name=\"Arch\">\n            <directory>tests/Arch</directory>\n        </testsuite>\n    </testsuites>");
});

it('is idempotent: a second pass changes nothing', function (): void {
    $patcher = new PhpunitXmlPatcher;
    $once = $patcher->addTestsuite(laravelPhpunitXml(), 'Arch', 'tests/Arch');
    $twice = $patcher->addTestsuite((string) $once['xml'], 'Arch', 'tests/Arch');

    expect($twice['status'])->toBe('already-covered')
        ->and($twice['xml'])->toBe($once['xml']);
});

it('leaves the file alone when a testsuite already covers the directory under another name', function (): void {
    $xml = str_replace('name="Arch"', 'name="Architecture"', (string) (new PhpunitXmlPatcher)
        ->addTestsuite(laravelPhpunitXml(), 'Arch', 'tests/Arch')['xml']);

    $result = (new PhpunitXmlPatcher)->addTestsuite($xml, 'Arch', 'tests/Arch');

    expect($result['status'])->toBe('already-covered')
        ->and($result['xml'])->toBe($xml);
});

it('treats a parent directory as covering the arch directory', function (): void {
    $xml = str_replace('<directory>tests/Unit</directory>', '<directory>tests</directory>', laravelPhpunitXml());

    $result = (new PhpunitXmlPatcher)->addTestsuite($xml, 'Arch', 'tests/Arch');

    expect($result['status'])->toBe('already-covered')
        ->and($result['xml'])->toBe($xml);
});

it('does not mistake a <source> directory for test coverage', function (): void {
    $xml = str_replace('<directory>app</directory>', '<directory>tests/Arch</directory>', laravelPhpunitXml());

    $result = (new PhpunitXmlPatcher)->addTestsuite($xml, 'Arch', 'tests/Arch');

    expect($result['status'])->toBe('added')
        ->and($result['xml'])->toContain('<testsuite name="Arch">');
});

it('refuses to touch invalid XML', function (): void {
    $result = (new PhpunitXmlPatcher)->addTestsuite('<phpunit><testsuites></phpunit>', 'Arch', 'tests/Arch');

    expect($result['status'])->toBe('invalid')
        ->and($result['xml'])->toBeNull();
});

it('refuses to touch a file with no testsuites element', function (): void {
    $result = (new PhpunitXmlPatcher)->addTestsuite('<?xml version="1.0"?><phpunit></phpunit>', 'Arch', 'tests/Arch');

    expect($result['status'])->toBe('invalid')
        ->and($result['xml'])->toBeNull();
});
