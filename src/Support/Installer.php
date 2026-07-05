<?php

declare(strict_types=1);

namespace MohamedAshrafElsaed\ClaudeKit\Support;

use Illuminate\Filesystem\Filesystem;

/**
 * Scaffolds the selected parts of the kit into a host project. Pure filesystem
 * work — no shell-outs — so it is fully testable against a temp directory. The
 * command layer handles prompts and git configuration.
 */
final class Installer
{
    /**
     * @var list<array{action: string, path: string}>
     */
    private array $report = [];

    public function __construct(
        private readonly Filesystem $files,
        private readonly string $basePath,
        private readonly string $stubsPath,
    ) {}

    /**
     * @param  list<string>  $parts
     * @return list<array{action: string, path: string}>
     */
    public function run(array $parts, FrontendStack $stack, bool $force, string $projectName): array
    {
        $this->report = [];

        if (in_array(Part::Claude->value, $parts, true)) {
            $this->installClaudeCore($stack, $force);
        }

        if (in_array(Part::Rules->value, $parts, true)) {
            $this->installRules($stack, $force, $projectName);
        }

        if (in_array(Part::Quality->value, $parts, true)) {
            $this->installQualityGate($force);
        }

        if (in_array(Part::Frontend->value, $parts, true)) {
            $this->installFrontend($stack, $force);
        }

        if (in_array(Part::Docs->value, $parts, true)) {
            $this->installDocs($force);
        }

        if (in_array(Part::Ci->value, $parts, true)) {
            $this->installCi($force);
        }

        return $this->report;
    }

    private function installClaudeCore(FrontendStack $stack, bool $force): void
    {
        $this->copyStub('claude/settings.json', '.claude/settings.json', $force);
        $this->copyStub('mcp.json', '.mcp.json', $force);

        foreach ($stack->skills() as $skill) {
            $this->copyTree("claude/skills/{$skill}", ".claude/skills/{$skill}", $force);
        }
    }

    private function installRules(FrontendStack $stack, bool $force, string $projectName): void
    {
        $this->copyStub('CLAUDE.md.stub', 'CLAUDE.md', $force, [
            '{{PROJECT_NAME}}' => $projectName,
            '{{FRONTEND_RULES}}' => $stack->claudeRules(),
        ]);
    }

    private function installQualityGate(bool $force): void
    {
        $this->copyStub('phpstan.neon.stub', 'phpstan.neon', $force);
        $this->copyStub('pint.json.stub', 'pint.json', $force);
        $this->copyStub('tests/Arch/ArchTest.php.stub', 'tests/Arch/ArchTest.php', $force);

        $this->copyStub('githooks/pre-commit', '.githooks/pre-commit', $force);
        $hook = $this->basePath.'/.githooks/pre-commit';

        if ($this->files->exists($hook)) {
            $this->files->chmod($hook, 0755);
        }

        (new ComposerJsonMerger($this->files))->merge(
            $this->basePath.'/composer.json',
            [
                'lint' => ['pint --parallel'],
                'lint:check' => ['pint --parallel --test'],
                'types:check' => ['phpstan analyse'],
                'hooks:install' => ['@php -r "is_dir(\'.git\') && shell_exec(\'git config core.hooksPath .githooks\');"'],
            ],
            ['@hooks:install'],
        );
        $this->record('merged', 'composer.json');
    }

    private function installFrontend(FrontendStack $stack, bool $force): void
    {
        $stubDirectory = $stack->stubDirectory();

        if ($stubDirectory === null) {
            $this->record('skipped', 'frontend (API-only project)');

            return;
        }

        $source = $this->stubsPath.'/frontend/'.$stubDirectory;

        foreach ($this->files->files($source, hidden: true) as $file) {
            $this->writeFile($file->getPathname(), $file->getFilename(), $force);
        }

        (new PackageJsonMerger($this->files))->merge(
            $this->basePath.'/package.json',
            $stack->npmScripts(),
            $stack->devDependencies(),
        );
        $this->record('merged', 'package.json');
    }

    private function installDocs(bool $force): void
    {
        $this->copyTree('features/_TEMPLATE', 'features/_TEMPLATE', $force);
        $this->copyStub('features/README.md', 'features/README.md', $force);
        $this->copyStub('editorconfig', '.editorconfig', $force);
        $this->copyStub('gitattributes', '.gitattributes', $force);
    }

    private function installCi(bool $force): void
    {
        $this->copyStub('github/workflows/tests.yml', '.github/workflows/tests.yml', $force);
        $this->copyStub('github/workflows/lint.yml', '.github/workflows/lint.yml', $force);
    }

    /**
     * @param  array<string, string>  $replacements
     */
    private function copyStub(string $stubRelative, string $targetRelative, bool $force, array $replacements = []): void
    {
        $this->writeFile($this->stubsPath.'/'.$stubRelative, $targetRelative, $force, $replacements);
    }

    private function copyTree(string $stubRelative, string $targetRelative, bool $force): void
    {
        $source = $this->stubsPath.'/'.$stubRelative;

        foreach ($this->files->allFiles($source, hidden: true) as $file) {
            $relative = ltrim(substr($file->getPathname(), strlen($source)), '/');
            $this->writeFile($file->getPathname(), $targetRelative.'/'.$relative, $force);
        }
    }

    /**
     * @param  array<string, string>  $replacements
     */
    private function writeFile(string $sourceAbsolute, string $targetRelative, bool $force, array $replacements = []): void
    {
        $target = $this->basePath.'/'.$targetRelative;
        $existed = $this->files->exists($target);

        if ($existed && ! $force) {
            $this->record('skipped', $targetRelative);

            return;
        }

        $contents = $this->files->get($sourceAbsolute);

        if ($replacements !== []) {
            $contents = strtr($contents, $replacements);
        }

        $this->files->ensureDirectoryExists(dirname($target));
        $this->files->put($target, $contents);

        $this->record($existed ? 'overwritten' : 'created', $targetRelative);
    }

    private function record(string $action, string $path): void
    {
        $this->report[] = ['action' => $action, 'path' => $path];
    }
}
