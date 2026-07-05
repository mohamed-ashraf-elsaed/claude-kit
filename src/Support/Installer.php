<?php

declare(strict_types=1);

namespace MohamedAshrafElsaed\ClaudeKit\Support;

use Illuminate\Filesystem\Filesystem;

/**
 * Scaffolds the selected setup into a host project based on an InstallOptions.
 * Pure filesystem work — no shell-outs — so it is fully testable against a temp
 * directory. Prompts and skills.sh calls live in the command layer.
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
     * @return list<array{action: string, path: string}>
     */
    public function run(InstallOptions $options, string $projectName): array
    {
        $this->report = [];

        $this->installClaudeCore($options);

        if ($options->wants('rules')) {
            $this->copyStub('CLAUDE.md.stub', 'CLAUDE.md', $options->force, [
                '{{PROJECT_NAME}}' => $projectName,
                '{{FRONTEND_RULES}}' => $options->stack->claudeRules(),
            ]);
        }

        $this->installQualityGate($options);
        $this->installFrontend($options);
        $this->installDocs($options);

        if ($options->wants('ci')) {
            $this->copyStub('github/workflows/tests.yml', '.github/workflows/tests.yml', $options->force);
            $this->copyStub('github/workflows/lint.yml', '.github/workflows/lint.yml', $options->force);
        }

        $this->writeManifest($options);

        return $this->report;
    }

    private function installClaudeCore(InstallOptions $options): void
    {
        $this->putFile('.claude/settings.json', $this->renderSettings($options), $options->force);

        if ($options->wants('mcp')) {
            $this->copyStub('mcp.json', '.mcp.json', $options->force);
        }

        foreach ($options->skills as $skill) {
            if ($this->files->isDirectory($this->stubsPath.'/claude/skills/'.$skill)) {
                $this->copyTree("claude/skills/{$skill}", ".claude/skills/{$skill}", $options->force);
            }
        }
    }

    private function installQualityGate(InstallOptions $options): void
    {
        if ($options->pint) {
            $this->copyStub('pint.json.stub', 'pint.json', $options->force);
        }

        if ($options->phpstan) {
            $this->putFile('phpstan.neon', $this->renderPhpstan($options), $options->force);
        }

        if ($options->runsArchitectureTests()) {
            $this->copyStub('tests/Arch/ArchTest.php.stub', 'tests/Arch/ArchTest.php', $options->force);
        }

        if ($options->hasHook('pre-commit')) {
            $this->copyStub('githooks/pre-commit', '.githooks/pre-commit', $options->force);
            $hook = $this->basePath.'/.githooks/pre-commit';

            if ($this->files->exists($hook)) {
                $this->files->chmod($hook, 0755);
            }
        }

        $this->mergeComposerScripts($options);
    }

    private function installFrontend(InstallOptions $options): void
    {
        $stubDirectory = $options->stack->stubDirectory();

        if ($stubDirectory === null) {
            return;
        }

        $source = $this->stubsPath.'/frontend/'.$stubDirectory;

        foreach ($this->files->files($source, hidden: true) as $file) {
            $this->writeFile($file->getPathname(), $file->getFilename(), $options->force);
        }

        (new PackageJsonMerger($this->files))->merge(
            $this->basePath.'/package.json',
            $options->stack->npmScripts(),
            $options->stack->devDependencies(),
        );
        $this->record('merged', 'package.json');
    }

    private function installDocs(InstallOptions $options): void
    {
        if ($options->wants('docs')) {
            $this->copyTree('features/_TEMPLATE', 'features/_TEMPLATE', $options->force);
            $this->copyStub('features/README.md', 'features/README.md', $options->force);
        }

        if ($options->wants('editorconfig')) {
            $this->copyStub('editorconfig', '.editorconfig', $options->force);
            $this->copyStub('gitattributes', '.gitattributes', $options->force);
        }
    }

    private function mergeComposerScripts(InstallOptions $options): void
    {
        $scripts = [];

        if ($options->pint) {
            $scripts['lint'] = ['pint --parallel'];
            $scripts['lint:check'] = ['pint --parallel --test'];
        }

        if ($options->phpstan) {
            $scripts['types:check'] = ['phpstan analyse'];
        }

        $postAutoloadDump = [];

        if ($options->hasHook('pre-commit')) {
            $scripts['hooks:install'] = ['@php -r "is_dir(\'.git\') && shell_exec(\'git config core.hooksPath .githooks\');"'];
            $postAutoloadDump[] = '@hooks:install';
        }

        if ($scripts === [] && $postAutoloadDump === []) {
            return;
        }

        (new ComposerJsonMerger($this->files))->merge($this->basePath.'/composer.json', $scripts, $postAutoloadDump);
        $this->record('merged', 'composer.json');
    }

    private function renderSettings(InstallOptions $options): string
    {
        $allow = ['Bash(php artisan:*)', 'Bash(composer:*)'];

        if ($options->pint) {
            $allow[] = 'Bash(vendor/bin/pint:*)';
        }

        if ($options->phpstan) {
            $allow[] = 'Bash(vendor/bin/phpstan:*)';
        }

        if ($options->tests) {
            $allow[] = 'Bash('.$options->testTool->binary().':*)';
        }

        if ($options->stack->hasFrontendTooling()) {
            $allow[] = 'Bash(npm run:*)';
        }

        $settings = ['$schema' => 'https://json.schemastore.org/claude-code-settings.json'];

        if ($options->hasHook('stop')) {
            $settings['hooks'] = [
                'Stop' => [[
                    'hooks' => [[
                        'type' => 'command',
                        'command' => 'bash "$CLAUDE_PROJECT_DIR/vendor/mohamed-ashraf-elsaed/claude-kit/runtime/hooks/stop-validate.sh"',
                    ]],
                ]],
            ];
        }

        $settings['permissions'] = ['allow' => $allow];

        return $this->encodeJson($settings);
    }

    private function renderPhpstan(InstallOptions $options): string
    {
        $includes = [
            '    - vendor/larastan/larastan/extension.neon',
            '    - vendor/nesbot/carbon/extension.neon',
        ];

        if ($options->phpstanStrict) {
            $includes[] = '    - vendor/phpstan/phpstan-strict-rules/rules.neon';
        }

        return implode("\n", [
            '# Generated by claude-kit. Adjust paths and level to suit your app.',
            'includes:',
            implode("\n", $includes),
            '',
            'parameters:',
            '    level: '.$options->phpstanLevel,
            '    paths:',
            '        - app/',
            '        - bootstrap/app.php',
            '        - config/',
            '        - database/',
            '        - routes/',
            '',
        ]);
    }

    private function writeManifest(InstallOptions $options): void
    {
        $manifest = [
            'stack' => $options->stack->value,
            'tests' => [
                'enabled' => $options->tests,
                'tool' => $options->testTool->value,
                'coverage_min' => $options->coverageMin,
            ],
            'hooks' => [
                'feature_docs' => $options->hasHook('feature-docs'),
            ],
        ];

        $this->putFile('.claude-kit.json', $this->encodeJson($manifest), true);
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
        $contents = $this->files->get($sourceAbsolute);

        if ($replacements !== []) {
            $contents = strtr($contents, $replacements);
        }

        $this->putFile($targetRelative, $contents, $force);
    }

    private function putFile(string $targetRelative, string $contents, bool $force): void
    {
        $target = $this->basePath.'/'.$targetRelative;
        $existed = $this->files->exists($target);

        if ($existed && ! $force) {
            $this->record('skipped', $targetRelative);

            return;
        }

        $this->files->ensureDirectoryExists(dirname($target));
        $this->files->put($target, $contents);

        $this->record($existed ? 'overwritten' : 'created', $targetRelative);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function encodeJson(array $data): string
    {
        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE).PHP_EOL;
    }

    private function record(string $action, string $path): void
    {
        $this->report[] = ['action' => $action, 'path' => $path];
    }
}
