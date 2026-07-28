# CLAUDE.md — developing `claude-kit`

This file orients Claude Code (and humans) working **on this package**. Read it
fully before changing anything — it exists to prevent hallucination and to let a
fresh session continue without re-deriving the design. It is authoritative;
where it conflicts with generic habits, **this file wins.**

> ⚠️ **Two different CLAUDE.md files — do not confuse them.**
> - **This file** (`/CLAUDE.md`) governs development *of the package*.
> - `stubs/CLAUDE.md.stub` is a **template shipped to other projects**; it is
>   product content, not instructions for us. Editing one is not editing the other.

## What this package is

`claude-kit` is a **Composer dev package** that scaffolds a Laravel project for
Claude Code in one command: engineering rules, a Stop-hook + pre-commit quality
gate, reusable skills, PHPStan (level 7 + strict-rules) / Pint / Pest, an
architecture test suite, feature-doc templates, and **stack-aware** frontend
tooling (Inertia+Vue, Inertia+React, Blade/Livewire, or API-only).

- Composer name: **`mohamed-ashraf-elsaed/claude-kit`**
- PHP namespace: **`MohamedAshrafElsaed\ClaudeKit`** (PSR-4, `src/`)
- Supports PHP **8.2–8.4** and Laravel **11 / 12 / 13**.
- Installed by consumers with `composer require --dev` then `php artisan claude-kit:install`.

> **Dependency contract (do not undo).** `require` holds only `illuminate/*` and
> `laravel/prompts`. Pint, Larastan, phpstan-strict-rules, Pest, and
> pest-plugin-laravel live in `suggest` (and in our own `require-dev`), because
> requiring them forced a Pest/PHPUnit version on every consumer and could not
> resolve against a fresh Laravel skeleton. The installer computes the exact
> `composer require --dev …` line from the selected options instead.

## Repository map (know this before editing)

```
src/
  ClaudeKitServiceProvider.php     Registers both commands (auto-discovered)
  Commands/
    InstallCommand.php             `claude-kit:install` — prompts + flags, THIN
    UpdateCommand.php              `claude-kit:update` — manifest → options, THIN
    Concerns/PromptsForFileConflicts.php   Shared keep/replace/diff prompt (both commands)
  Support/
    FrontendStack.php              Enum: per-stack skills/scripts/deps/config/rule/prose
    TestTool.php                   Enum: Pest | PHPUnit
    CleanCodePreset.php            Enum: strict | balanced | relaxed → rule stub
    InstallOptions.php             Readonly DTO: every choice + defaults() + fromManifest()
    StackDetector.php              Detects the stack from composer.json + package.json
    Installer.php                  The workhorse — pure filesystem, no shell-outs
    FileWriter.php                 ALL writes go through it; also the line diff
    StubRenderer.php               Reads a stub, substitutes {{PLACEHOLDER}}
    Manifest.php                   .claude/claude-kit.json (+ legacy fallback) + file hashes
    SettingsMerger.php             Deep, non-destructive .claude/settings.json merge
    ManagedBlock.php               claude-kit:begin/end region inside CLAUDE.md
    PhpunitXmlPatcher.php          Adds a tests/Arch testsuite to the host phpunit.xml
    SkillInstaller.php             Wraps `npx skills find/add` (skills.sh)
    PackageJsonMerger.php          Idempotent, non-destructive package.json merge
    ComposerJsonMerger.php         Idempotent, non-destructive composer.json merge
runtime/                           REFERENCED from vendor/ in host projects (auto-update)
  quality-checks.sh                Host gate: Pint/PHPStan (presence) + tests (manifest) +
                                   frontend; CLAUDE_KIT_CHECKS filters the PHP checks
  hooks/stop-validate.sh           Host Stop hook: N consecutive gate runs + feature docs
stubs/                             PUBLISHED (copied) into host projects
  CLAUDE.md.stub                   Short host rules; {{PROJECT_NAME}} {{ARCHITECTURE_LAYERS}}
                                   {{FRONTEND_RULES}} {{RULES_INDEX}} {{QUALITY_GATE}} {{FEATURE_DOCS}}
  claude/rules/**                  architecture-<layer> · clean-code.{strict,balanced,relaxed}
                                   frontend-<stack> · anti-* · worktrees
  claude/skills/**                 The 7 bundled (Laravel-authored) skills
  claude/workflows/**              OUR skills: commit (+convention.md.stub) deploy dead-code codemap
  claude/agents/**                 design-reviewer · docs-reviewer (read-only)
  claude/output-styles/concise.md  Selected via settings.json only when chosen
  claude/hooks/phpstan-check.sh    Published + rendered ({{PHPSTAN_MODE}}, {{PHPSTAN_LEVEL}})
  tests/Arch/ArchTest.php.stub     Base blocks; layers/*.php.stub appended per selection
  pint.json.stub                   (phpstan.neon + settings.json are GENERATED)
  githooks/pre-commit  editorconfig  gitattributes  mcp.json
  features/{_TEMPLATE/*, README.md}
  github/workflows/{tests,lint}.yml   (host CI — NOT this repo's CI)
  frontend/{inertia-vue,inertia-react,blade}/*   (livewire reuses blade/)
tests/                             Pest: Unit (logic) + Feature (commands via Testbench)
docs/                              Wiki pages (also pushed to the GitHub Wiki)
bin/quality-checks.sh              THIS repo's own gate (Pint/PHPStan/Pest + changelog rule)
.githooks/pre-commit               THIS repo's committed pre-commit gate
.github/workflows/                 THIS repo's CI: tests.yml, lint.yml, release.yml
```

## The hybrid model (the single most important concept)

- **`runtime/` is referenced, not copied.** Host projects call it at
  `vendor/mohamed-ashraf-elsaed/claude-kit/runtime/...`, so a `composer update`
  propagates fixes everywhere. Changing a runtime file changes behavior for
  every consumer — treat it as an API. The host hooks and the generated
  `phpstan.neon` reference vendor paths; keep them stable.
- **`stubs/` is published (copied).** Consumers own their copy; updates reach
  them only when they run `claude-kit:update` (or `install --force`), which uses
  the per-file hashes in the manifest to refresh untouched files silently and ask
  about edited ones. So put *machinery* in `runtime/` and *content/opinion* in
  `stubs/`. The one published *script* is `.claude/hooks/phpstan-check.sh`,
  because it is rendered per project.

## How the installer works (don't re-derive)

`InstallCommand` (thin) resolves the stack (`--stack` flag → else `StackDetector`
→ else `select`), then builds an **`InstallOptions`** — interactively via
`promptOptions()` (six titled sections: code style & static analysis, tests,
architecture, enforcement hooks, reviewers/skills/rules, scaffolding) or via
`InstallOptions::defaults()` when non-interactive. It hands that to
`Installer::run($options, $projectName)`, then optionally runs the skills.sh
finder (`SkillInstaller`) and wires the git hook.

`UpdateCommand` (thin) reads the manifest, rebuilds the same DTO via
`InstallOptions::fromManifest()`, and runs the **same** `Installer` — with the
recorded file hashes, so an untouched file is refreshed silently and only a file
the team edited reaches the prompt. It asks nothing about *what* to install.

`Installer` is **pure filesystem** (testable against a temp dir):
- Generates `.claude/settings.json` (**merged**, never clobbered — see
  `SettingsMerger`), `phpstan.neon` (level + larastan + strict toggles),
  `tests/Arch/ArchTest.php` (base stub + one fragment per selected layer), the
  CLAUDE.md managed block, and the `.claude/claude-kit.json` manifest that the
  runtime gate and `claude-kit:update` read.
- **Every** write goes through `FileWriter`, which is the single place deciding
  created / unchanged / updated / overwritten / skipped. Prompting is *not* here:
  it calls the `$conflictResolver` closure the command layer injected.
  `putMerged()` is for content that already incorporates the project's own
  (settings.json, CLAUDE.md) and therefore never asks.
- Frontend + composer changes go through the two mergers, which **preserve
  existing keys** and are **idempotent**.
- `FrontendStack` is the one place per-stack differences live (skills, npm
  scripts, devDependencies, stub directory, frontend rule stub, CLAUDE.md prose).
  Add a stack by extending this enum + `stubs/frontend/<dir>/` +
  `stubs/claude/rules/frontend-<stack>.md`.
- `Installer::LAYERS` + `stubs/tests/Arch/layers/` are the one place per-layer
  differences live. **A declined layer must get no rule, no arch block, and no
  CLAUDE.md mention** — there are tests for exactly this.
- The runtime gate runs Pint/PHPStan when their config files exist and tests per
  the manifest — so what the user selected is exactly what runs. The Stop hook
  additionally repeats it `hooks.min_passes` times and honours `hooks.checks`.

**Honest hook semantics** (verified against the Claude Code docs — do not
"improve" these): a `PostToolUse` hook runs *after* the edit, so exit 2 cannot
revert it; it only feeds the errors back to Claude (`block`) versus warning the
user via `systemMessage` (`warn`). Only the `Stop` hook truly blocks (exit 2).

## Working here — commands

```bash
composer install          # PHP 8.2+; pulls testbench, pest, pint, phpstan, larastan
composer check            # Pint + PHPStan (0 errors) + Pest — the full gate
composer lint             # auto-fix style
composer test:coverage    # Pest + 80% gate (needs pcov/Xdebug)
git config core.hooksPath .githooks   # enable the local pre-commit gate
```

## Non-negotiable conventions

- `declare(strict_types=1);` everywhere; explicit types on every signature.
- `final` classes; small, single-responsibility units; **no dead/speculative code**.
- PHPStan level 7 + strict-rules must report **zero** errors. Never add
  `@phpstan-ignore`, baselines, or casts to silence it — fix the cause.
- Every behavior change ships with a test. Feature tests drive the installer
  against a **temp directory** and must be deterministic (no network/globals).
  `stubs_path()` and `temp_project()` helpers live in `tests/Pest.php`.
- Match surrounding style; Pint's Laravel preset is the arbiter.

## Release rule (MANDATORY — enforced)

Every user-facing change — **feature or fix** — must add an entry under
`## [Unreleased]` in `CHANGELOG.md` in the same change. This is enforced by
`bin/quality-checks.sh` (and thus the pre-commit + Stop hooks): if `src/`,
`runtime/`, or `stubs/` changed and `CHANGELOG.md` did not, the gate fails.

When a change (or a batch of them) warrants a release, **cut it**: follow
`RELEASING.md` — move `[Unreleased]` to `[X.Y.Z]`, bump per SemVer, tag `vX.Y.Z`,
and push the tag (which triggers `.github/workflows/release.yml`). Do not push a
tag for every commit — tag when the accumulated changes are worth a version.
Packagist updates automatically from the tag.

## Current state

- Unreleased (on top of v0.2.2): `.claude/rules/` conventions with DRAFT markers,
  six independent architecture layers driving both rules and a **generated**
  `ArchTest`, three clean-code presets, read-only reviewer agents, the
  `/commit` `/deploy` `/dead-code` `/codemap` workflow skills, an edit-time
  PHPStan hook, the Stop-hook `min_passes` verification loop, a concise output
  style, `claude-kit:update`, per-file conflict resolution with a built-in diff,
  a deep-merged `settings.json`, a delimited CLAUDE.md block, the manifest at
  `.claude/claude-kit.json` (legacy root path still read), and **5** stacks
  (Livewire split from Blade). See `CHANGELOG.md` for the authoritative status.

## Content rules for stubs (product content, not our code)

- A convention **we** authored — not stated by the user — ships marked
  `<!-- DRAFT -->` so the consumer reviews it before adopting it. Never present a
  drafted convention as settled.
- Rule stubs must not reference an architecture layer other than their own, so a
  project that declined a layer never sees it named.
- Skills stay under 150 lines with valid frontmatter and no nested file
  references. `/commit` and `/deploy` carry `disable-model-invocation: true`
  because Claude must never decide to commit or deploy on its own.
- Never invent a project-specific value (a deploy command, a commit convention).
  Ask, or ship a clearly marked TODO.
- Do not contradict the bundled `laravel-best-practices` skill; it is
  Laravel-authored content we republish untouched.

## Pointers

- Contributing workflow → `CONTRIBUTING.md`
- Release/versioning → `RELEASING.md`
- Architecture deep-dive & rationale → `docs/Architecture.md`
- Publishing to Packagist → `docs/Publishing.md`
