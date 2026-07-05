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

## Repository map (know this before editing)

```
src/
  ClaudeKitServiceProvider.php     Registers the command (auto-discovered)
  Commands/InstallCommand.php      `claude-kit:install` — prompts + flags, THIN
  Support/
    FrontendStack.php              Enum: per-stack skills/scripts/deps/config/prose
    Part.php                       Enum: installable parts (claude,rules,quality,frontend,docs,ci)
    StackDetector.php              Detects the stack from composer.json + package.json
    Installer.php                  The workhorse — pure filesystem, no shell-outs
    PackageJsonMerger.php          Idempotent, non-destructive package.json merge
    ComposerJsonMerger.php         Idempotent, non-destructive composer.json merge
runtime/                           REFERENCED from vendor/ in host projects (auto-update)
  quality-checks.sh                Host gate: PHP + conditional frontend
  hooks/stop-validate.sh           Host Stop hook: gate + feature-doc requirement
  phpstan/base.neon                level 7 + strict-rules + larastan (relative includes)
stubs/                             PUBLISHED (copied) into host projects
  claude/{settings.json, skills/**}
  CLAUDE.md.stub                   Generic host rules with {{PROJECT_NAME}}/{{FRONTEND_RULES}}
  phpstan.neon.stub  pint.json.stub  tests/Arch/ArchTest.php.stub
  githooks/pre-commit  editorconfig  gitattributes
  features/{_TEMPLATE/*, README.md}
  github/workflows/{tests,lint}.yml   (host CI — NOT this repo's CI)
  frontend/{inertia-vue,inertia-react,blade}/*
tests/                             Pest: Unit (logic) + Feature (command via Testbench)
docs/                              Wiki pages (also pushed to the GitHub Wiki)
bin/quality-checks.sh              THIS repo's own gate (Pint/PHPStan/Pest + changelog rule)
.claude/ .githooks/                THIS repo dogfooding the gate
.github/workflows/                 THIS repo's CI: tests.yml, lint.yml, release.yml
```

## The hybrid model (the single most important concept)

- **`runtime/` is referenced, not copied.** Host projects call it at
  `vendor/mohamed-ashraf-elsaed/claude-kit/runtime/...`, so a `composer update`
  propagates fixes everywhere. Changing a runtime file changes behavior for
  every consumer — treat it as an API. The host `phpstan.neon` and hooks point
  into this path; `base.neon`'s `../../../../` includes assume that exact vendor
  depth — do not move it.
- **`stubs/` is published (copied) once.** Consumers own their copy; we cannot
  push updates to an already-installed stub except via `install --force`. So put
  *machinery* in `runtime/` and *content/opinion* in `stubs/`.

## How the installer works (don't re-derive)

`InstallCommand` (thin) resolves the stack (`--stack` flag → else `StackDetector`
→ else interactive `select`) and the parts (`--parts` → else all → else
`multiselect`), then delegates to `Installer::run($parts, $stack, $force, $projectName)`.

`Installer` is **pure filesystem** (testable against a temp dir):
- `copyStub` / `copyTree` write files, skipping existing ones unless `--force`,
  applying `{{PLACEHOLDER}}` replacements; every write is recorded as
  created/overwritten/skipped and returned as the report.
- Frontend + composer changes go through the two mergers, which **preserve
  existing keys** (existing wins) and are **idempotent** — re-running changes nothing.
- `FrontendStack` is the one place per-stack differences live (skills, npm
  scripts, devDependencies, stub directory, CLAUDE.md prose). Add a stack by
  extending this enum + adding `stubs/frontend/<dir>/`.

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

- v0.1.0 scaffolded and green: install command, 4 stacks, hybrid runtime/stubs,
  idempotent mergers, full test suite (Unit + Feature), CI (tests/lint/release),
  and all community-health docs. See `CHANGELOG.md` for the authoritative status.

## Pointers

- Contributing workflow → `CONTRIBUTING.md`
- Release/versioning → `RELEASING.md`
- Architecture deep-dive & rationale → `docs/Architecture.md`
- Publishing to Packagist → `docs/Publishing.md`
