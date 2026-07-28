<?php

declare(strict_types=1);

use Illuminate\Filesystem\Filesystem;
use MohamedAshrafElsaed\ClaudeKit\Support\CleanCodePreset;
use MohamedAshrafElsaed\ClaudeKit\Support\FrontendStack;
use MohamedAshrafElsaed\ClaudeKit\Support\Installer;
use MohamedAshrafElsaed\ClaudeKit\Support\InstallOptions;
use MohamedAshrafElsaed\ClaudeKit\Support\TestTool;

function installer(string $dir, ?Closure $resolver = null, ?Closure $notify = null): Installer
{
    return new Installer(new Filesystem, $dir, stubs_path(), $resolver, $notify);
}

function laravelSkeleton(string $dir): void
{
    $files = new Filesystem;
    $files->ensureDirectoryExists($dir.'/app');
    $files->put($dir.'/composer.json', json_encode(['name' => 'acme/app', 'scripts' => []]));
}

function readJson(string $path): array
{
    return json_decode((new Filesystem)->get($path), true);
}

function laravelPhpunitFixture(): string
{
    return <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<phpunit bootstrap="vendor/autoload.php" colors="true">
    <testsuites>
        <testsuite name="Unit">
            <directory>tests/Unit</directory>
        </testsuite>
        <testsuite name="Feature">
            <directory>tests/Feature</directory>
        </testsuite>
    </testsuites>
</phpunit>

XML;
}

/**
 * The full option set with only the given keys changed, so a test states just
 * the choice it is about.
 */
function optionsWith(array $overrides, FrontendStack $stack = FrontendStack::None): InstallOptions
{
    $base = InstallOptions::defaults($stack);

    return new InstallOptions(
        stack: $overrides['stack'] ?? $base->stack,
        pint: $overrides['pint'] ?? $base->pint,
        phpstan: $overrides['phpstan'] ?? $base->phpstan,
        phpstanLevel: $overrides['phpstanLevel'] ?? $base->phpstanLevel,
        phpstanStrict: $overrides['phpstanStrict'] ?? $base->phpstanStrict,
        phpstanLarastan: $overrides['phpstanLarastan'] ?? $base->phpstanLarastan,
        phpstanHook: $overrides['phpstanHook'] ?? $base->phpstanHook,
        tests: $overrides['tests'] ?? $base->tests,
        testTool: $overrides['testTool'] ?? $base->testTool,
        coverageMin: array_key_exists('coverageMin', $overrides) ? $overrides['coverageMin'] : $base->coverageMin,
        archTests: $overrides['archTests'] ?? $base->archTests,
        architecture: $overrides['architecture'] ?? $base->architecture,
        cleanCode: $overrides['cleanCode'] ?? $base->cleanCode,
        hooks: $overrides['hooks'] ?? $base->hooks,
        validationChecks: $overrides['validationChecks'] ?? $base->validationChecks,
        minPasses: $overrides['minPasses'] ?? $base->minPasses,
        agents: $overrides['agents'] ?? $base->agents,
        workflowSkills: $overrides['workflowSkills'] ?? $base->workflowSkills,
        deployCommand: $overrides['deployCommand'] ?? $base->deployCommand,
        commitConvention: array_key_exists('commitConvention', $overrides) ? $overrides['commitConvention'] : $base->commitConvention,
        behavioralRules: $overrides['behavioralRules'] ?? $base->behavioralRules,
        outputStyle: $overrides['outputStyle'] ?? $base->outputStyle,
        worktreeRule: $overrides['worktreeRule'] ?? $base->worktreeRule,
        skills: $overrides['skills'] ?? $base->skills,
        scaffolding: $overrides['scaffolding'] ?? $base->scaffolding,
        force: $overrides['force'] ?? $base->force,
    );
}

it('installs the full kit for an Inertia + Vue project', function (): void {
    $dir = temp_project();
    laravelSkeleton($dir);

    installer($dir)->run(InstallOptions::defaults(FrontendStack::InertiaVue), 'Acme');

    $files = new Filesystem;
    expect($files->exists($dir.'/.claude/settings.json'))->toBeTrue()
        ->and($files->exists($dir.'/.claude/claude-kit.json'))->toBeTrue()
        ->and($files->exists($dir.'/.mcp.json'))->toBeTrue()
        ->and($files->exists($dir.'/.claude/skills/inertia-vue-development/SKILL.md'))->toBeTrue()
        ->and($files->exists($dir.'/.claude/skills/commit/SKILL.md'))->toBeTrue()
        ->and($files->exists($dir.'/.claude/skills/deploy/SKILL.md'))->toBeTrue()
        ->and($files->exists($dir.'/.claude/skills/dead-code/SKILL.md'))->toBeTrue()
        ->and($files->exists($dir.'/.claude/skills/codemap/SKILL.md'))->toBeTrue()
        ->and($files->exists($dir.'/.claude/agents/design-reviewer.md'))->toBeTrue()
        ->and($files->exists($dir.'/.claude/agents/docs-reviewer.md'))->toBeTrue()
        ->and($files->exists($dir.'/.claude/output-styles/concise.md'))->toBeTrue()
        ->and($files->exists($dir.'/.claude/hooks/phpstan-check.sh'))->toBeTrue()
        ->and($files->exists($dir.'/.claude/rules/clean-code.md'))->toBeTrue()
        ->and($files->exists($dir.'/.claude/rules/frontend.md'))->toBeTrue()
        ->and($files->exists($dir.'/.claude/rules/anti-overengineering.md'))->toBeTrue()
        ->and($files->exists($dir.'/.claude/rules/anti-assumption.md'))->toBeTrue()
        ->and($files->exists($dir.'/.claude/rules/worktrees.md'))->toBeTrue()
        ->and($files->exists($dir.'/CLAUDE.md'))->toBeTrue()
        ->and($files->exists($dir.'/phpstan.neon'))->toBeTrue()
        ->and($files->exists($dir.'/pint.json'))->toBeTrue()
        ->and($files->exists($dir.'/tests/Arch/ArchTest.php'))->toBeTrue()
        ->and($files->exists($dir.'/.githooks/pre-commit'))->toBeTrue()
        ->and($files->exists($dir.'/eslint.config.js'))->toBeTrue()
        ->and($files->exists($dir.'/.github/workflows/tests.yml'))->toBeTrue()
        ->and(is_executable($dir.'/.claude/hooks/phpstan-check.sh'))->toBeTrue()
        ->and(is_executable($dir.'/.githooks/pre-commit'))->toBeTrue();
});

it('publishes one rule per selected architecture layer and nothing for the rest', function (): void {
    $dir = temp_project();
    laravelSkeleton($dir);

    installer($dir)->run(optionsWith(['architecture' => ['services', 'contracts']]), 'Acme');

    $files = new Filesystem;
    expect($files->exists($dir.'/.claude/rules/architecture-services.md'))->toBeTrue()
        ->and($files->exists($dir.'/.claude/rules/architecture-contracts.md'))->toBeTrue()
        ->and($files->exists($dir.'/.claude/rules/architecture-repositories.md'))->toBeFalse()
        ->and($files->exists($dir.'/.claude/rules/architecture-data-objects.md'))->toBeFalse()
        ->and($files->exists($dir.'/.claude/rules/architecture-value-objects.md'))->toBeFalse()
        ->and($files->exists($dir.'/.claude/rules/architecture-actions.md'))->toBeFalse();
});

it('generates an arch test suite containing only the selected layers', function (): void {
    $dir = temp_project();
    laravelSkeleton($dir);

    installer($dir)->run(optionsWith(['architecture' => ['services', 'value-objects']]), 'Acme');

    $arch = (new Filesystem)->get($dir.'/tests/Arch/ArchTest.php');

    expect($arch)->toContain('App\Services')
        ->and($arch)->toContain('App\ValueObjects')
        ->and($arch)->toContain('toUseStrictTypes')
        ->and($arch)->toContain('App\Models')
        ->and($arch)->not->toContain('App\Repositories')
        ->and($arch)->not->toContain('App\Actions')
        ->and($arch)->not->toContain('App\Data')
        ->and($arch)->not->toContain('App\Contracts');
});

it('enforces the one-public-method rule the actions convention documents', function (): void {
    $dir = temp_project();
    laravelSkeleton($dir);

    installer($dir)->run(optionsWith(['architecture' => ['actions']]), 'Acme');

    $arch = (new Filesystem)->get($dir.'/tests/Arch/ArchTest.php');

    // The rule states an action exposes exactly one public method; without this
    // expectation that sentence would be a claim the suite does not back.
    expect($arch)->toContain("->not->toHavePublicMethodsBesides(['__construct', '__invoke'])")
        ->and($arch)->toContain("->toHaveMethod('__invoke')");
});

it('never mentions a declined layer in CLAUDE.md', function (): void {
    $dir = temp_project();
    laravelSkeleton($dir);

    installer($dir)->run(optionsWith(['architecture' => ['services']]), 'Acme');

    $claude = (new Filesystem)->get($dir.'/CLAUDE.md');

    expect($claude)->toContain('app/Services')
        ->and($claude)->not->toContain('app/Repositories')
        ->and($claude)->not->toContain('app/ValueObjects')
        ->and($claude)->not->toContain('app/Actions')
        ->and($claude)->not->toContain('app/Contracts');
});

it('says the project uses no extra layers when none were selected', function (): void {
    $dir = temp_project();
    laravelSkeleton($dir);

    installer($dir)->run(optionsWith(['architecture' => []]), 'Acme');

    $claude = (new Filesystem)->get($dir.'/CLAUDE.md');

    expect($claude)->toContain("Laravel's defaults")
        ->and((new Filesystem)->get($dir.'/tests/Arch/ArchTest.php'))->not->toContain('App\Services');
});

it('publishes the chosen clean-code preset as one rule file', function (): void {
    $dir = temp_project();
    laravelSkeleton($dir);

    installer($dir)->run(optionsWith(['cleanCode' => CleanCodePreset::Strict]), 'Acme');

    $rule = (new Filesystem)->get($dir.'/.claude/rules/clean-code.md');

    expect($rule)->toContain('Clean code — strict')
        ->and($rule)->toContain('20 lines')
        ->and((new Filesystem)->exists($dir.'/.claude/rules/clean-code.strict.md'))->toBeFalse();
});

it('registers a tests/Arch testsuite in the host phpunit.xml', function (): void {
    $dir = temp_project();
    laravelSkeleton($dir);
    (new Filesystem)->put($dir.'/phpunit.xml', laravelPhpunitFixture());

    $report = installer($dir)->run(optionsWith([]), 'Acme');
    $xml = (new Filesystem)->get($dir.'/phpunit.xml');

    expect($xml)->toContain('<testsuite name="Arch">')
        ->and($xml)->toContain('<directory>tests/Arch</directory>')
        ->and($xml)->toContain('<testsuite name="Unit">')
        ->and($report)->toContain(['action' => 'merged', 'path' => 'phpunit.xml']);
});

it('leaves phpunit.xml untouched on a second run', function (): void {
    $dir = temp_project();
    laravelSkeleton($dir);
    (new Filesystem)->put($dir.'/phpunit.xml', laravelPhpunitFixture());

    installer($dir)->run(optionsWith([]), 'Acme');
    $after = (new Filesystem)->get($dir.'/phpunit.xml');
    $report = installer($dir)->run(optionsWith([]), 'Acme');

    expect((new Filesystem)->get($dir.'/phpunit.xml'))->toBe($after)
        ->and($report)->toContain(['action' => 'unchanged', 'path' => 'phpunit.xml']);
});

it('hands back a manual step when phpunit.xml is missing or unparseable', function (): void {
    $dir = temp_project();
    laravelSkeleton($dir);

    $missing = installer($dir);
    $missing->run(optionsWith([]), 'Acme');

    expect($missing->manualSteps())->toHaveCount(1)
        ->and($missing->manualSteps()[0])->toContain('No phpunit.xml found');

    (new Filesystem)->put($dir.'/phpunit.xml', '<phpunit><testsuites></phpunit>');
    $broken = installer($dir);
    $broken->run(optionsWith([]), 'Acme');

    expect($broken->manualSteps()[0])->toContain('could not be parsed')
        ->and((new Filesystem)->get($dir.'/phpunit.xml'))->toBe('<phpunit><testsuites></phpunit>');
});

it('does not touch phpunit.xml when the arch suite is not installed', function (): void {
    $dir = temp_project();
    laravelSkeleton($dir);
    (new Filesystem)->put($dir.'/phpunit.xml', laravelPhpunitFixture());

    installer($dir)->run(optionsWith(['testTool' => TestTool::PHPUnit]), 'Acme');

    expect((new Filesystem)->get($dir.'/phpunit.xml'))->toBe(laravelPhpunitFixture())
        ->and((new Filesystem)->exists($dir.'/tests/Arch/ArchTest.php'))->toBeFalse();
});

it('keeps config and bootstrap out of the default phpstan paths', function (): void {
    $dir = temp_project();
    laravelSkeleton($dir);

    installer($dir)->run(optionsWith([]), 'Acme');
    $neon = (new Filesystem)->get($dir.'/phpstan.neon');

    expect($neon)->toContain('- app/')
        ->and($neon)->toContain('- database/')
        ->and($neon)->toContain('- routes/')
        ->and($neon)->not->toContain('config/')
        ->and($neon)->not->toContain('bootstrap');
});

it('renders the chosen PHPStan level, strict rules and larastan into phpstan.neon', function (): void {
    $dir = temp_project();
    laravelSkeleton($dir);

    installer($dir)->run(optionsWith([
        'pint' => false,
        'phpstanLevel' => 9,
        'phpstanStrict' => false,
        'phpstanLarastan' => false,
    ]), 'Acme');

    $neon = (new Filesystem)->get($dir.'/phpstan.neon');

    expect($neon)->toContain('level: 9')
        ->and($neon)->not->toContain('larastan')
        ->and($neon)->not->toContain('phpstan-strict-rules')
        ->and((new Filesystem)->exists($dir.'/pint.json'))->toBeFalse();
});

it('wires the phpstan hook into settings.json and renders its mode', function (): void {
    $dir = temp_project();
    laravelSkeleton($dir);

    installer($dir)->run(optionsWith(['phpstanHook' => 'warn', 'phpstanLevel' => 6]), 'Acme');

    $settings = readJson($dir.'/.claude/settings.json');
    $hook = (new Filesystem)->get($dir.'/.claude/hooks/phpstan-check.sh');

    expect($settings['hooks']['PostToolUse'][0]['matcher'])->toBe('Edit|Write')
        ->and($settings['hooks']['PostToolUse'][0]['hooks'][0]['command'])->toContain('.claude/hooks/phpstan-check.sh')
        ->and($hook)->toContain('MODE="warn"')
        ->and($hook)->toContain('level 6')
        ->and($hook)->not->toContain('{{');
});

it('omits the hooks and the output style from settings.json when they are not selected', function (): void {
    $dir = temp_project();
    laravelSkeleton($dir);

    installer($dir)->run(optionsWith([
        'phpstan' => false,
        'phpstanHook' => 'off',
        'hooks' => ['pre-commit'],
        'outputStyle' => false,
    ]), 'Acme');

    $settings = readJson($dir.'/.claude/settings.json');

    expect($settings)->not->toHaveKey('hooks')
        ->and($settings)->not->toHaveKey('outputStyle')
        ->and($settings['permissions']['allow'])->toContain('Bash(vendor/bin/pint:*)')
        ->and($settings['permissions']['allow'])->not->toContain('Bash(vendor/bin/phpstan:*)')
        ->and((new Filesystem)->exists($dir.'/.claude/output-styles/concise.md'))->toBeFalse();
});

it('selects the concise output style only when it was chosen', function (): void {
    $dir = temp_project();
    laravelSkeleton($dir);

    installer($dir)->run(optionsWith(['outputStyle' => true]), 'Acme');

    expect(readJson($dir.'/.claude/settings.json')['outputStyle'])->toBe('Concise')
        ->and((new Filesystem)->get($dir.'/.claude/output-styles/concise.md'))->toContain('keep-coding-instructions: true');
});

it('merges settings.json instead of clobbering what the project already had', function (): void {
    $dir = temp_project();
    laravelSkeleton($dir);
    $files = new Filesystem;
    $files->ensureDirectoryExists($dir.'/.claude');
    $files->put($dir.'/.claude/settings.json', json_encode([
        'permissions' => ['allow' => ['Bash(gh:*)'], 'deny' => ['Read(.env)']],
        'outputStyle' => 'Explanatory',
        'env' => ['APP_ENV' => 'testing'],
    ]));

    $notices = [];
    $installer = installer($dir, null, function (string $message) use (&$notices): void {
        $notices[] = $message;
    });
    $installer->run(optionsWith(['outputStyle' => true]), 'Acme');

    $settings = readJson($dir.'/.claude/settings.json');

    expect($settings['permissions']['allow'])->toContain('Bash(gh:*)')
        ->and($settings['permissions']['allow'])->toContain('Bash(composer:*)')
        ->and($settings['permissions']['deny'])->toBe(['Read(.env)'])
        ->and($settings['env']['APP_ENV'])->toBe('testing')
        ->and($settings['outputStyle'])->toBe('Explanatory')
        ->and($installer->settingsSummary()['kept'])->toBe(['outputStyle'])
        ->and($notices)->toHaveCount(1)
        ->and($notices[0])->toContain('kept (yours): outputStyle');
});

it('appends a managed block to an existing CLAUDE.md and keeps the project text', function (): void {
    $dir = temp_project();
    laravelSkeleton($dir);
    (new Filesystem)->put($dir.'/CLAUDE.md', "# Our own notes\n\nDo not lose this line.\n");

    $report = installer($dir)->run(optionsWith([]), 'Acme');

    $claude = (new Filesystem)->get($dir.'/CLAUDE.md');

    expect($claude)->toContain('Do not lose this line.')
        ->and($claude)->toContain('<!-- claude-kit:begin -->')
        ->and($claude)->toContain('<!-- claude-kit:end -->')
        // The file already had a title, so the block must not add a second one.
        ->and(preg_match_all('/^# /m', $claude))->toBe(1)
        ->and($claude)->toContain('# Our own notes')
        ->and($report)->toContain(['action' => 'merged', 'path' => 'CLAUDE.md']);
});

it('gives a freshly created CLAUDE.md its own title', function (): void {
    $dir = temp_project();
    laravelSkeleton($dir);

    installer($dir)->run(optionsWith([]), 'Acme');

    $claude = (new Filesystem)->get($dir.'/CLAUDE.md');

    expect($claude)->toContain('# Acme')
        ->and(preg_match_all('/^# /m', $claude))->toBe(1);
});

it('refreshes the managed block on a second run without duplicating it', function (): void {
    $dir = temp_project();
    laravelSkeleton($dir);
    $run = fn (): array => installer($dir)->run(optionsWith([]), 'Acme');

    $run();
    $first = (new Filesystem)->get($dir.'/CLAUDE.md');
    $run();
    $second = (new Filesystem)->get($dir.'/CLAUDE.md');

    expect($second)->toBe($first)
        ->and(substr_count($second, '<!-- claude-kit:begin -->'))->toBe(1);
});

it('writes a manifest describing every selection and the published file hashes', function (): void {
    $dir = temp_project();
    laravelSkeleton($dir);

    installer($dir)->run(optionsWith([
        'stack' => FrontendStack::Blade,
        'testTool' => TestTool::PHPUnit,
        'coverageMin' => null,
        'archTests' => false,
        'phpstanLevel' => 6,
        'hooks' => ['stop'],
        'validationChecks' => ['tests'],
        'minPasses' => 4,
        'architecture' => ['services'],
        'cleanCode' => CleanCodePreset::Relaxed,
        'deployCommand' => 'php artisan deploy',
        'commitConvention' => 'Ticket first: [ABC-123] summary',
    ], FrontendStack::Blade), 'Acme');

    $manifest = readJson($dir.'/.claude/claude-kit.json');

    expect($manifest['stack'])->toBe('blade')
        ->and($manifest['tests']['enabled'])->toBeTrue()
        ->and($manifest['tests']['tool'])->toBe('phpunit')
        ->and($manifest['tests']['coverage_min'])->toBeNull()
        ->and($manifest['tests']['arch'])->toBeFalse()
        ->and($manifest['phpstan']['level'])->toBe(6)
        ->and($manifest['hooks']['checks'])->toBe(['tests'])
        ->and($manifest['hooks']['min_passes'])->toBe(4)
        ->and($manifest['hooks']['feature_docs'])->toBeFalse()
        ->and($manifest['architecture'])->toBe(['services'])
        ->and($manifest['rules']['clean_code'])->toBe('relaxed')
        ->and($manifest['skills']['deploy_command'])->toBe('php artisan deploy')
        ->and($manifest['skills']['commit_convention'])->toBe('Ticket first: [ABC-123] summary')
        ->and($manifest['files'])->toHaveKey('CLAUDE.md')
        ->and($manifest['files']['CLAUDE.md'])->toBe(hash('sha256', (new Filesystem)->get($dir.'/CLAUDE.md')))
        ->and((new Filesystem)->exists($dir.'/tests/Arch/ArchTest.php'))->toBeFalse();
});

it('embeds a custom commit convention verbatim and a TODO when no deploy command was given', function (): void {
    $dir = temp_project();
    laravelSkeleton($dir);

    installer($dir)->run(optionsWith([
        'commitConvention' => "JIRA-first, always:\n[PROJ-1234] imperative summary",
        'deployCommand' => '',
    ]), 'Acme');

    $files = new Filesystem;
    $commit = $files->get($dir.'/.claude/skills/commit/SKILL.md');
    $deploy = $files->get($dir.'/.claude/skills/deploy/SKILL.md');

    expect($commit)->toContain('[PROJ-1234] imperative summary')
        ->and($commit)->not->toContain('Conventional Commits')
        ->and($commit)->not->toContain('{{COMMIT_CONVENTION}}')
        ->and($deploy)->toContain('TODO — no deploy command was configured')
        ->and($deploy)->not->toContain('{{DEPLOY_COMMAND_BLOCK}}');
});

it('uses the conventional commits stub when no custom convention was given', function (): void {
    $dir = temp_project();
    laravelSkeleton($dir);

    installer($dir)->run(optionsWith(['deployCommand' => 'make deploy']), 'Acme');

    $files = new Filesystem;

    expect($files->get($dir.'/.claude/skills/commit/SKILL.md'))->toContain('Conventional Commits')
        ->and($files->get($dir.'/.claude/skills/deploy/SKILL.md'))->toContain('make deploy');
});

it('installs the livewire frontend rule and only the prettier tooling', function (): void {
    $dir = temp_project();
    laravelSkeleton($dir);

    installer($dir)->run(InstallOptions::defaults(FrontendStack::Livewire), 'Acme');

    $files = new Filesystem;
    $package = readJson($dir.'/package.json');

    expect($files->get($dir.'/.claude/rules/frontend.md'))->toContain('Livewire + Blade')
        ->and($files->exists($dir.'/.prettierrc'))->toBeTrue()
        ->and($files->exists($dir.'/eslint.config.js'))->toBeFalse()
        ->and($package['scripts'])->toHaveKey('format:check')
        ->and($package['scripts'])->not->toHaveKey('types:check');
});

it('skips all frontend files and the frontend rule for an API-only project', function (): void {
    $dir = temp_project();
    laravelSkeleton($dir);

    installer($dir)->run(InstallOptions::defaults(FrontendStack::None), 'Acme');

    $files = new Filesystem;
    expect($files->exists($dir.'/eslint.config.js'))->toBeFalse()
        ->and($files->exists($dir.'/package.json'))->toBeFalse()
        ->and($files->exists($dir.'/.claude/rules/frontend.md'))->toBeFalse()
        ->and($files->exists($dir.'/.claude/skills/laravel-best-practices/SKILL.md'))->toBeTrue();
});

it('leaves an existing file alone when nobody can be asked about it', function (): void {
    $dir = temp_project();
    laravelSkeleton($dir);
    (new Filesystem)->put($dir.'/pint.json', 'KEEP ME');

    $report = installer($dir)->run(optionsWith([]), 'Acme');

    expect((new Filesystem)->get($dir.'/pint.json'))->toBe('KEEP ME')
        ->and($report)->toContain(['action' => 'skipped', 'path' => 'pint.json']);
});

it('asks the resolver about a differing file and obeys it', function (): void {
    $dir = temp_project();
    laravelSkeleton($dir);
    $files = new Filesystem;
    $files->put($dir.'/pint.json', 'MINE');
    $files->put($dir.'/.editorconfig', 'MINE');

    $asked = [];
    installer($dir, function (string $path) use (&$asked): string {
        $asked[] = $path;

        return $path === 'pint.json' ? 'overwrite' : 'skip';
    })->run(optionsWith([]), 'Acme');

    expect($files->get($dir.'/pint.json'))->not->toBe('MINE')
        ->and($files->get($dir.'/.editorconfig'))->toBe('MINE')
        ->and($asked)->toContain('pint.json', '.editorconfig');
});

it('overwrites everything without asking when forced', function (): void {
    $dir = temp_project();
    laravelSkeleton($dir);
    (new Filesystem)->put($dir.'/pint.json', 'OLD');

    installer($dir, function (): string {
        throw new RuntimeException('--force must not ask');
    })->run(optionsWith(['force' => true]), 'Acme');

    expect((new Filesystem)->get($dir.'/pint.json'))->not->toBe('OLD');
});

it('is idempotent across repeated runs', function (): void {
    $dir = temp_project();
    laravelSkeleton($dir);

    $run = fn (): array => installer($dir)->run(InstallOptions::defaults(FrontendStack::InertiaVue), 'Acme');

    $run();
    $files = new Filesystem;
    $package = $files->get($dir.'/package.json');
    $composer = $files->get($dir.'/composer.json');
    $settings = $files->get($dir.'/.claude/settings.json');

    $second = $run();

    expect($files->get($dir.'/package.json'))->toBe($package)
        ->and($files->get($dir.'/composer.json'))->toBe($composer)
        ->and($files->get($dir.'/.claude/settings.json'))->toBe($settings)
        ->and($second)->toContain(['action' => 'unchanged', 'path' => 'pint.json'])
        ->and($second)->toContain(['action' => 'unchanged', 'path' => '.claude/rules/clean-code.md']);
});
