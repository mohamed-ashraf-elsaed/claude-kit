<?php

declare(strict_types=1);

namespace MohamedAshrafElsaed\ClaudeKit\Support;

use DOMDocument;
use DOMElement;
use DOMText;

/**
 * Adds a testsuite to a project's phpunit.xml so the architecture tests are
 * actually discovered. Laravel's stock file declares only tests/Unit and
 * tests/Feature, which means a generated tests/Arch/ArchTest.php would never
 * run — not from `php artisan test`, and not from the quality gate.
 *
 * Works on the XML the project already has and returns it unchanged when the
 * directory is already covered, so re-running changes nothing.
 */
final class PhpunitXmlPatcher
{
    /**
     * @return array{xml: string|null, status: string} status: added, already-covered, or invalid
     */
    public function addTestsuite(string $xml, string $name, string $directory): array
    {
        $document = new DOMDocument;
        $document->preserveWhiteSpace = true;
        $document->formatOutput = false;

        $reporting = libxml_use_internal_errors(true);
        $loaded = $document->loadXML($xml);
        libxml_clear_errors();
        libxml_use_internal_errors($reporting);

        if ($loaded === false) {
            return ['xml' => null, 'status' => 'invalid'];
        }

        $suites = $document->getElementsByTagName('testsuites')->item(0);

        if (! $suites instanceof DOMElement) {
            return ['xml' => null, 'status' => 'invalid'];
        }

        if ($this->alreadyCovered($suites, $directory)) {
            return ['xml' => $xml, 'status' => 'already-covered'];
        }

        $suite = $this->buildSuite($document, $name, $directory);
        $last = $suites->lastChild;

        if ($last instanceof DOMText) {
            // Re-use the whitespace that indented the closing tag: the existing
            // run becomes this child's indent, and the closing tag keeps its own.
            $closing = $last->nodeValue ?? "\n    ";
            $last->nodeValue = "\n        ";
            $suites->appendChild($suite);
            $suites->appendChild($document->createTextNode($closing));
        } else {
            $suites->appendChild($suite);
        }

        $saved = $document->saveXML();

        return $saved === false
            ? ['xml' => null, 'status' => 'invalid']
            : ['xml' => $saved, 'status' => 'added'];
    }

    /**
     * True when some existing testsuite already picks the directory up — either
     * exactly, or through a parent such as <directory>tests</directory>.
     */
    private function alreadyCovered(DOMElement $suites, string $directory): bool
    {
        foreach ($suites->getElementsByTagName('directory') as $candidate) {
            $covered = trim($candidate->textContent);

            if ($covered === '') {
                continue;
            }

            if ($covered === $directory || str_starts_with($directory, rtrim($covered, '/').'/')) {
                return true;
            }
        }

        return false;
    }

    /**
     * The new node, with its child indented the way phpunit.xml normally is.
     */
    private function buildSuite(DOMDocument $document, string $name, string $directory): DOMElement
    {
        $suite = $document->createElement('testsuite');
        $suite->setAttribute('name', $name);
        $suite->appendChild($document->createTextNode("\n            "));
        $suite->appendChild($document->createElement('directory', $directory));
        $suite->appendChild($document->createTextNode("\n        "));

        return $suite;
    }
}
