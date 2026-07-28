<?php

declare(strict_types=1);

namespace MohamedAshrafElsaed\ClaudeKit\Support;

use Closure;
use Illuminate\Filesystem\Filesystem;

/**
 * Scaffolds the selected setup into a host project based on an InstallOptions.
 * Pure filesystem work — no shell-outs — so it is fully testable against a temp
 * directory. Prompts and skills.sh calls live in the command layer; what to do
 * with a file that already exists arrives as the $conflictResolver closure.
 */
final class Installer
{
    /**
     * The one place the per-layer differences live: the directory each selected
     * architecture layer owns, and the line describing it in CLAUDE.md.
     */
    private const LAYERS = [
        'services' => ['label' => 'Services', 'dir' => 'app/Services', 'summary' => 'business logic and orchestration'],
        'repositories' => ['label' => 'Repositories', 'dir' => 'app/Repositories', 'summary' => 'named, reusable persistence queries'],
        'data-objects' => ['label' => 'Data Objects', 'dir' => 'app/Data', 'summary' => 'typed, immutable structures that cross layer boundaries'],
        'value-objects' => ['label' => 'Value Objects', 'dir' => 'app/ValueObjects', 'summary' => 'small immutable types that enforce their own invariants'],
        'actions' => ['label' => 'Actions', 'dir' => 'app/Actions', 'summary' => 'one invokable class per business operation'],
        'contracts' => ['label' => 'Contracts', 'dir' => 'app/Contracts', 'summary' => 'interfaces for the boundaries this app does not control'],
    ];

    /**
     * One line per published rule for the CLAUDE.md index.
     */
    private const RULE_INDEX = [
        'clean-code.md' => 'size, control flow, naming, and what must not exist in the codebase',
        'frontend.md' => 'where frontend code lives and what it may do',
        'anti-overengineering.md' => 'build the smallest thing that satisfies the requirement',
        'anti-assumption.md' => 'verify against the code or ask — never invent a fact',
        'worktrees.md' => 'running more than one feature at a time with git worktrees',
    ];

    private const OUTPUT_STYLE = 'Concise';

    private const STOP_HOOK = 'bash "$CLAUDE_PROJECT_DIR/vendor/mohamed-ashraf-elsaed/claude-kit/runtime/hooks/stop-validate.sh"';

    private const PHPSTAN_HOOK = 'bash "$CLAUDE_PROJECT_DIR/.claude/hooks/phpstan-check.sh"';

    private readonly StubRenderer $stubs;

    private readonly Manifest $manifest;

    private FileWriter $writer;

    /**
     * @var list<string>
     */
    private array $publishedRules = [];

    /**
     * @var array{added: list<string>, changed: list<string>, kept: list<string>}
     */
    private array $settingsSummary = ['added' => [], 'changed' => [], 'kept' => []];

    /**
     * Things the installer could not do for the project and must hand back.
     *
     * @var list<string>
     */
    private array $manualSteps = [];

    /**
     * @param  (Closure(string, string, string): string)|null  $conflictResolver  decides what to do with an existing file that differs
     * @param  (Closure(string): void)|null  $notify  displays the settings.json key summary before it is written
     * @param  array<string, string>  $publishedHashes  from the manifest; lets an untouched file be refreshed without asking
     */
    public function __construct(
        private readonly Filesystem $files,
        private readonly string $basePath,
        private readonly string $stubsPath,
        private readonly ?Closure $conflictResolver = null,
        private readonly ?Closure $notify = null,
        private readonly array $publishedHashes = [],
    ) {
        $this->stubs = new StubRenderer($this->files, $this->stubsPath);
        $this->manifest = new Manifest($this->files, $this->basePath);
        $this->writer = new FileWriter($this->files, $this->basePath, false);
    }

    /**
     * @return list<array{action: string, path: string}>
     */
    public function run(InstallOptions $options, string $projectName): array
    {
        $this->writer = new FileWriter(
            $this->files,
            $this->basePath,
            $options->force,
            $this->conflictResolver,
            $this->publishedHashes,
        );
        $this->publishedRules = [];
        $this->settingsSummary = ['added' => [], 'changed' => [], 'kept' => []];
        $this->manualSteps = [];

        $this->installClaudeCore($options);
        $this->installRules($options);
        $this->installAgents($options);
        $this->installWorkflowSkills($options);
        $this->installOutputStyle($options);
        $this->installClaudeMd($options, $projectName);
        $this->installQualityGate($options);
        $this->installFrontend($options);
        $this->installDocs($options);

        if ($options->wants('ci')) {
            $this->copyStub('github/workflows/tests.yml', '.github/workflows/tests.yml');
            $this->copyStub('github/workflows/lint.yml', '.github/workflows/lint.yml');
        }

        $this->writeManifest($options, $projectName);

        return $this->writer->report();
    }

    /**
     * Which `.claude/settings.json` keys the merge added, changed, or left alone.
     *
     * @return array{added: list<string>, changed: list<string>, kept: list<string>}
     */
    public function settingsSummary(): array
    {
        return $this->settingsSummary;
    }

    /**
     * Steps the project has to perform by hand, because the installer refused to
     * guess (an unparseable phpunit.xml, for instance).
     *
     * @return list<string>
     */
    public function manualSteps(): array
    {
        return $this->manualSteps;
    }

    private function installClaudeCore(InstallOptions $options): void
    {
        $this->installSettings($options);

        if ($options->wants('mcp')) {
            $this->copyStub('mcp.json', '.mcp.json');
        }

        foreach ($options->skills as $skill) {
            if ($this->files->isDirectory($this->stubs->path('claude/skills/'.$skill))) {
                $this->copyTree("claude/skills/{$skill}", ".claude/skills/{$skill}");
            }
        }

        if ($options->installsPhpstanHook()) {
            $this->copyStub('claude/hooks/phpstan-check.sh', '.claude/hooks/phpstan-check.sh', [
                '{{PHPSTAN_MODE}}' => $options->phpstanHook,
                '{{PHPSTAN_LEVEL}}' => (string) $options->phpstanLevel,
            ]);
            $this->writer->chmod('.claude/hooks/phpstan-check.sh', 0755);
        }
    }

    /**
     * Merges what claude-kit needs into `.claude/settings.json` without
     * disturbing anything the project already configured.
     */
    private function installSettings(InstallOptions $options): void
    {
        $target = $this->basePath.'/.claude/settings.json';
        $existing = [];

        if ($this->files->exists($target)) {
            $decoded = json_decode($this->files->get($target), true);
            $existing = is_array($decoded) ? $decoded : [];
        }

        $merger = new SettingsMerger;
        /** @var array<string, mixed> $existing */
        $merged = $merger->merge($this->renderSettings($options), $existing);
        $this->settingsSummary = $merger->summary();

        $this->announceSettings();
        $this->writer->putMerged('.claude/settings.json', $this->encodeJson($merged));
    }

    private function installRules(InstallOptions $options): void
    {
        if (! $options->wants('rules')) {
            return;
        }

        foreach (array_keys(self::LAYERS) as $layer) {
            if ($options->hasLayer($layer)) {
                $this->publishRule("architecture-{$layer}.md", "claude/rules/architecture-{$layer}.md");
            }
        }

        $this->publishRule('clean-code.md', $options->cleanCode->stub());

        $frontend = $options->stack->ruleStub();

        if ($frontend !== null) {
            $this->publishRule('frontend.md', $frontend);
        }

        foreach ($options->behavioralRules as $rule) {
            $this->publishRule("{$rule}.md", "claude/rules/{$rule}.md");
        }

        if ($options->worktreeRule) {
            $this->publishRule('worktrees.md', 'claude/rules/worktrees.md');
        }
    }

    private function publishRule(string $name, string $stub): void
    {
        $this->copyStub($stub, ".claude/rules/{$name}");
        $this->publishedRules[] = $name;
    }

    private function installAgents(InstallOptions $options): void
    {
        foreach ($options->agents as $agent) {
            $this->copyStub("claude/agents/{$agent}.md", ".claude/agents/{$agent}.md");
        }
    }

    private function installWorkflowSkills(InstallOptions $options): void
    {
        foreach ($options->workflowSkills as $skill) {
            $this->copyStub("claude/workflows/{$skill}/SKILL.md", ".claude/skills/{$skill}/SKILL.md", match ($skill) {
                'commit' => ['{{COMMIT_CONVENTION}}' => $this->commitConvention($options)],
                'deploy' => ['{{DEPLOY_COMMAND_BLOCK}}' => $this->deployCommandBlock($options)],
                default => [],
            });
        }
    }

    private function commitConvention(InstallOptions $options): string
    {
        if ($options->commitConvention === null) {
            return trim($this->stubs->render('claude/workflows/commit/convention.md.stub'));
        }

        return "This project's commit message convention, exactly as the team stated it:\n\n"
            .$options->commitConvention;
    }

    private function deployCommandBlock(InstallOptions $options): string
    {
        if ($options->deployCommand === '') {
            return "**TODO — no deploy command was configured when claude-kit was installed.**\n"
                ."Ask the user for the exact command and record it here. Do not guess one,\n"
                .'and do not run a deploy command you inferred from the repository.';
        }

        return "Run this project's deploy command:\n\n```bash\n".$options->deployCommand."\n```";
    }

    private function installOutputStyle(InstallOptions $options): void
    {
        if ($options->outputStyle) {
            $this->copyStub('claude/output-styles/concise.md', '.claude/output-styles/concise.md');
        }
    }

    private function installClaudeMd(InstallOptions $options, string $projectName): void
    {
        if (! $options->wants('rules')) {
            return;
        }

        $body = $this->stubs->render('CLAUDE.md.stub', [
            '{{PROJECT_NAME}}' => $projectName,
            '{{ARCHITECTURE_LAYERS}}' => $this->architectureSection($options),
            '{{FRONTEND_RULES}}' => $options->stack->claudeRules(),
            '{{RULES_INDEX}}' => $this->rulesIndex(),
            '{{QUALITY_GATE}}' => $this->qualityGateSection($options),
            '{{FEATURE_DOCS}}' => $this->featureDocsSection($options),
        ]);

        $target = $this->basePath.'/CLAUDE.md';
        $existing = $this->files->exists($target) ? $this->files->get($target) : '';

        $this->writer->putMerged('CLAUDE.md', ManagedBlock::applyTo($existing, $body));
    }

    private function architectureSection(InstallOptions $options): string
    {
        $lines = [];

        foreach (self::LAYERS as $layer => $meta) {
            if ($options->hasLayer($layer)) {
                $lines[] = "- **{$meta['label']}** (`{$meta['dir']}`) — {$meta['summary']}. Conventions:\n  `.claude/rules/architecture-{$layer}.md`.";
            }
        }

        if ($lines === []) {
            return 'This project stays on Laravel\'s defaults — controllers, form requests, and'
                ."\nmodels. No additional layers were adopted, so do not introduce one without\nasking.";
        }

        return implode("\n", $lines);
    }

    private function rulesIndex(): string
    {
        $lines = [];

        foreach ($this->publishedRules as $rule) {
            $description = self::RULE_INDEX[$rule] ?? $this->layerRuleDescription($rule);
            $lines[] = "- `.claude/rules/{$rule}` — {$description}";
        }

        return $lines === [] ? '- No convention rules were installed.' : implode("\n", $lines);
    }

    private function layerRuleDescription(string $rule): string
    {
        $layer = str_replace(['architecture-', '.md'], '', $rule);
        $meta = self::LAYERS[$layer] ?? null;

        return $meta === null ? 'project convention' : "{$meta['label']} — {$meta['summary']}";
    }

    private function qualityGateSection(InstallOptions $options): string
    {
        $checks = [];

        if ($options->pint) {
            $checks[] = '**Pint** for code style (it also adds `declare(strict_types=1)`)';
        }

        if ($options->phpstan) {
            $strict = $options->phpstanStrict ? ' with `phpstan-strict-rules`' : '';
            $checks[] = "**PHPStan** at **level {$options->phpstanLevel}**{$strict} — zero errors";
        }

        if ($options->tests) {
            $coverage = $options->coverageMin === null ? '' : ", coverage held at **{$options->coverageMin}%**";
            $checks[] = '**'.$options->testTool->label()."**{$coverage}";
        }

        if ($options->stack->hasFrontendTooling()) {
            $checks[] = 'the frontend checks your `package.json` defines';
        }

        $lines = $checks === []
            ? ['`vendor/mohamed-ashraf-elsaed/claude-kit/runtime/quality-checks.sh` is the gate.']
            : [
                '`vendor/mohamed-ashraf-elsaed/claude-kit/runtime/quality-checks.sh` runs:',
                '',
                ...array_map(static fn (string $check): string => "- {$check}", $checks),
            ];

        if ($options->hasHook('stop') && $options->minPasses > 1) {
            $lines[] = '';
            $lines[] = "The Stop hook requires **{$options->minPasses} consecutive clean runs** before a";
            $lines[] = 'turn may finish, so a check that only passes intermittently still blocks.';
        }

        if ($options->installsPhpstanHook()) {
            $lines[] = '';
            $lines[] = $options->phpstanHook === 'block'
                ? 'Every edited PHP file is analysed immediately by the PostToolUse hook, and the'."\nerrors come straight back to be fixed."
                : 'Every edited PHP file is analysed immediately by the PostToolUse hook, which'."\nwarns without interrupting.";
        }

        return implode("\n", $lines);
    }

    private function featureDocsSection(InstallOptions $options): string
    {
        if (! $options->hasHook('feature-docs')) {
            return '';
        }

        return "\n## Feature documentation (enforced)\n\n"
            ."Every feature and every bug fix is documented under `features/<feature-name>/`,\n"
            ."with both `FEATURE.md` and `DEPLOY.md` — copy the format from\n"
            ."`features/_TEMPLATE/`. The Stop hook blocks the turn when code under `app/`,\n"
            ."`database/`, `routes/`, or `resources/js/` changed and no feature doc was added\n"
            ."or updated. A bug fix appends to the relevant feature's **Change Log** instead of\n"
            ."creating a new folder. Write them as part of finishing the work, so they describe\n"
            ."what was actually built.\n";
    }

    private function installQualityGate(InstallOptions $options): void
    {
        if ($options->pint) {
            $this->copyStub('pint.json.stub', 'pint.json');
        }

        if ($options->phpstan) {
            $this->writer->put('phpstan.neon', $this->renderPhpstan($options));
        }

        if ($options->runsArchitectureTests()) {
            $this->writer->put('tests/Arch/ArchTest.php', $this->renderArchTest($options));
            $this->registerArchTestsuite();
        }

        if ($options->hasHook('pre-commit')) {
            $this->copyStub('githooks/pre-commit', '.githooks/pre-commit');
            $this->writer->chmod('.githooks/pre-commit', 0755);
        }

        $this->mergeComposerScripts($options);
    }

    /**
     * Laravel's phpunit.xml declares only tests/Unit and tests/Feature, so
     * without this the generated architecture suite would never be discovered.
     * The file is only ever added to — never rewritten from a stub.
     */
    private function registerArchTestsuite(): void
    {
        $target = $this->basePath.'/phpunit.xml';

        if (! $this->files->exists($target)) {
            $this->manualSteps[] = 'No phpunit.xml found — add a `tests/Arch` testsuite to your test config, or the architecture tests will not run.';

            return;
        }

        $result = (new PhpunitXmlPatcher)->addTestsuite($this->files->get($target), 'Arch', 'tests/Arch');

        if ($result['xml'] === null) {
            $this->manualSteps[] = 'phpunit.xml could not be parsed, so it was left alone — add a `tests/Arch` testsuite yourself, or the architecture tests will not run.';

            return;
        }

        $this->writer->putMerged('phpunit.xml', $result['xml']);
    }

    /**
     * Only the layers the project selected get an arch() block, so the suite can
     * never demand a directory the team declined.
     */
    private function renderArchTest(InstallOptions $options): string
    {
        $test = rtrim($this->stubs->render('tests/Arch/ArchTest.php.stub'), "\n");

        foreach (array_keys(self::LAYERS) as $layer) {
            if ($options->hasLayer($layer)) {
                $test .= "\n\n".rtrim($this->stubs->render("tests/Arch/layers/{$layer}.php.stub"), "\n");
            }
        }

        return $test."\n";
    }

    private function installFrontend(InstallOptions $options): void
    {
        $stubDirectory = $options->stack->stubDirectory();

        if ($stubDirectory === null) {
            return;
        }

        $source = $this->stubs->path('frontend/'.$stubDirectory);

        foreach ($this->files->files($source, hidden: true) as $file) {
            $this->writer->put($file->getFilename(), $this->files->get($file->getPathname()));
        }

        (new PackageJsonMerger($this->files))->merge(
            $this->basePath.'/package.json',
            $options->stack->npmScripts(),
            $options->stack->devDependencies(),
        );
        $this->writer->record('merged', 'package.json');
    }

    private function installDocs(InstallOptions $options): void
    {
        if ($options->wants('docs')) {
            $this->copyTree('features/_TEMPLATE', 'features/_TEMPLATE');
            $this->copyStub('features/README.md', 'features/README.md');
        }

        if ($options->wants('editorconfig')) {
            $this->copyStub('editorconfig', '.editorconfig');
            $this->copyStub('gitattributes', '.gitattributes');
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
        $this->writer->record('merged', 'composer.json');
    }

    /**
     * @return array<string, mixed>
     */
    private function renderSettings(InstallOptions $options): array
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
        $hooks = [];

        if ($options->hasHook('stop')) {
            $hooks['Stop'] = [[
                'hooks' => [['type' => 'command', 'command' => self::STOP_HOOK]],
            ]];
        }

        if ($options->installsPhpstanHook()) {
            $hooks['PostToolUse'] = [[
                'matcher' => 'Edit|Write',
                'hooks' => [['type' => 'command', 'command' => self::PHPSTAN_HOOK]],
            ]];
        }

        if ($hooks !== []) {
            $settings['hooks'] = $hooks;
        }

        if ($options->outputStyle) {
            $settings['outputStyle'] = self::OUTPUT_STYLE;
        }

        $settings['permissions'] = ['allow' => $allow];

        return $settings;
    }

    private function renderPhpstan(InstallOptions $options): string
    {
        $includes = [];

        if ($options->phpstanLarastan) {
            $includes[] = '    - vendor/larastan/larastan/extension.neon';
            $includes[] = '    - vendor/nesbot/carbon/extension.neon';
        }

        if ($options->phpstanStrict) {
            $includes[] = '    - vendor/phpstan/phpstan-strict-rules/rules.neon';
        }

        $lines = ['# Generated by claude-kit. Adjust paths and level to suit your app.'];

        if ($includes !== []) {
            $lines[] = 'includes:';
            $lines[] = implode("\n", $includes);
            $lines[] = '';
        }

        return implode("\n", [
            ...$lines,
            'parameters:',
            '    level: '.$options->phpstanLevel,
            '    paths:',
            '        - app/',
            '        - database/',
            '        - routes/',
            '',
        ]);
    }

    private function writeManifest(InstallOptions $options, string $projectName): void
    {
        $files = array_merge($this->manifest->publishedHashes(), $this->writer->hashes());
        ksort($files);

        $action = $this->manifest->write([
            'project_name' => $projectName,
            'stack' => $options->stack->value,
            'pint' => $options->pint,
            'phpstan' => [
                'enabled' => $options->phpstan,
                'level' => $options->phpstanLevel,
                'strict' => $options->phpstanStrict,
                'larastan' => $options->phpstanLarastan,
                'hook' => $options->phpstanHook,
            ],
            'tests' => [
                'enabled' => $options->tests,
                'tool' => $options->testTool->value,
                'coverage_min' => $options->coverageMin,
                'arch' => $options->archTests,
            ],
            'architecture' => $options->architecture,
            'rules' => [
                'clean_code' => $options->cleanCode->value,
                'behavioral' => $options->behavioralRules,
                'worktrees' => $options->worktreeRule,
            ],
            'hooks' => [
                'enabled' => $options->hooks,
                'checks' => $options->validationChecks,
                'min_passes' => $options->minPasses,
                'feature_docs' => $options->hasHook('feature-docs'),
            ],
            'agents' => $options->agents,
            'skills' => [
                'bundled' => $options->skills,
                'workflow' => $options->workflowSkills,
                'deploy_command' => $options->deployCommand,
                'commit_convention' => $options->commitConvention,
            ],
            'output_style' => $options->outputStyle,
            'scaffolding' => $options->scaffolding,
            'files' => $files,
        ]);

        $this->writer->record($action, Manifest::PATH);
    }

    private function announceSettings(): void
    {
        if (! $this->notify instanceof Closure) {
            return;
        }

        $summary = array_filter([
            'added' => $this->settingsSummary['added'],
            'changed' => $this->settingsSummary['changed'],
            'kept (yours)' => $this->settingsSummary['kept'],
        ], static fn (array $keys): bool => $keys !== []);

        if ($summary === []) {
            return;
        }

        $lines = ['.claude/settings.json — keys this merge touches:'];

        foreach ($summary as $label => $keys) {
            $lines[] = "  {$label}: ".implode(', ', $keys);
        }

        ($this->notify)(implode("\n", $lines));
    }

    /**
     * @param  array<string, string>  $replacements
     */
    private function copyStub(string $stubRelative, string $targetRelative, array $replacements = []): void
    {
        $this->writer->put($targetRelative, $this->stubs->render($stubRelative, $replacements));
    }

    private function copyTree(string $stubRelative, string $targetRelative): void
    {
        $source = $this->stubs->path($stubRelative);

        foreach ($this->files->allFiles($source, hidden: true) as $file) {
            $relative = ltrim(substr($file->getPathname(), strlen($source)), '/');
            $this->writer->put($targetRelative.'/'.$relative, $this->files->get($file->getPathname()));
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function encodeJson(array $data): string
    {
        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE).PHP_EOL;
    }
}
