# Changelog

All notable changes to `claude-kit` are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Changed

- Package metadata only: the Composer `homepage` now points to the project page
  at mohamedashrafelsaed.com, the author is listed as Mohamed Ashraf Elsaed with
  a homepage and contact email, and `support.docs` points to the docs site at
  claude-kit.mohamedashrafelsaed.com. The installed output does not change.

## [0.3.0] - 2026-07-29

### Added

- **`.claude/rules/` conventions.** The installer now publishes path-scoped and
  always-loaded rule files instead of one monolithic CLAUDE.md: one rule per
  selected architecture layer, a clean-code rule in a **strict / balanced /
  relaxed** variant, a stack-specific frontend rule, the always-on
  anti-overengineering and anti-assumption rules, and a git-worktree workflow
  rule. The conventions ship as claude-kit's opinionated defaults, meant to be
  read and adapted rather than adopted blindly.
- **Six independent architecture choices** — Services, Repositories, Data
  Objects, Value Objects, Actions, Contracts. A layer you decline gets no rule,
  no `arch()` block, and no mention in CLAUDE.md.
- **`tests/Arch/ArchTest.php` is generated** from those choices, composed from
  per-layer fragments, so the suite can never demand a directory you declined.
  The Actions fragment enforces the documented one-public-method rule with
  `->not->toHavePublicMethodsBesides(['__construct', '__invoke'])`, so a second
  public method on an action fails the suite rather than merely contradicting
  the rule text.
- **Review agents.** Optional read-only `design-reviewer` (validates output
  against the stated design/plan) and `docs-reviewer` (validates output against
  supplied documents), both with `disallowedTools: Write, Edit`.
- **Workflow skills.** Optional `/commit` and `/deploy` (both
  `disable-model-invocation: true`), `/dead-code`, and `/codemap`. `/commit`
  takes Conventional Commits or your own convention, embedded verbatim;
  `/deploy` takes your deploy command, or leaves a marked TODO — it never
  invents one.
- **PHPStan PostToolUse hook** (`.claude/hooks/phpstan-check.sh`): analyses each
  PHP file as it is edited, either feeding the errors back (`block`) or warning
  via `systemMessage` (`warn`).
- **Stop-hook verification loop.** `min_passes` (1–5, default 2) requires that
  many consecutive clean runs before a turn may finish, and `checks` limits the
  Stop hook to the selected subset of Pint / PHPStan / tests.
- **Concise output style** (`.claude/output-styles/concise.md`, with
  `keep-coding-instructions: true`), selected via `outputStyle` only when chosen.
- **`php artisan claude-kit:update`** — re-publishes the stubs for the options
  recorded in the manifest. A file still matching the hash it was published with
  is refreshed silently; a file you have edited is never replaced without asking.
- **Per-file conflict resolution.** A re-run asks only about files whose content
  actually differs: keep / replace / show diff, plus keep-all and replace-all.
  `--force` replaces everything silently; `--no-interaction` keeps everything.
- **Livewire** as a first-class stack (`--stack=livewire`), detected from
  `livewire/livewire` in `composer.json` ahead of the generic Blade fallback.
- **The architecture suite is now registered so it actually runs.** Laravel's
  `phpunit.xml` declares only `tests/Unit` and `tests/Feature`, so a generated
  `tests/Arch/ArchTest.php` was never discovered — not by `php artisan test`, and
  not by the quality gate. When the arch suite is selected the installer adds a
  `tests/Arch` testsuite to `phpunit.xml` via DOM, non-destructively and
  idempotently. If the file is missing or unparseable it is left untouched and
  you get a manual next-step instead.
- Support classes: `Manifest`, `StubRenderer`, `SettingsMerger`, `ManagedBlock`,
  `CleanCodePreset`, `PhpunitXmlPatcher`, `FileWriter` (including a
  dependency-free line diff).

### Changed

- **claude-kit no longer installs the tools it configures.** `laravel/pint`,
  `larastan/larastan`, `phpstan/phpstan-strict-rules`, `pestphp/pest`, and
  `pestphp/pest-plugin-laravel` moved from `require` to `suggest`, so requiring
  claude-kit can no longer force a Pest/PHPUnit version on your project or fail
  to resolve against a fresh Laravel skeleton.
  **Migration:** tools are no longer auto-installed; the installer works out the
  exact `composer require --dev …` line from the options you chose — skipping
  anything already present — and offers to run it. Decline it, or run
  non-interactively, and the command is printed in the next steps instead.
  **Upgrading from 0.2.x, read this:** because these packages used to arrive as
  dependencies of claude-kit, `composer update` to 0.3.0 **may remove**
  `laravel/pint`, `larastan/larastan`, `phpstan/phpstan-strict-rules`,
  `pestphp/pest`, and `pestphp/pest-plugin-laravel` from your project if nothing
  else requires them. The gate then **skips** those checks rather than failing —
  now with a warning on stderr, but a green gate that verified less than you
  think. Fix it by running `php artisan claude-kit:install` (which re-proposes
  the exact list) or the printed `composer require --dev …`, and confirm with:

  ```bash
  composer show pestphp/pest larastan/larastan
  ```
- **The gate no longer skips a missing tool in silence.** When the manifest's
  `hooks.checks` names a check whose binary is absent, `quality-checks.sh` prints
  a warning naming the check, the missing binary, and the `composer require --dev`
  line that fixes it. The exit code is unaffected — it is a warning, not a
  failure — and a check the manifest does not ask for stays silent as before.
- When the offered `composer require --dev …` fails on the known Pest ↔ PHPUnit
  conflict, the installer now says so explicitly and prints both the original
  command and the `-W` variant that resolves it — it never adds `-W` itself.
- **Green-by-default gate.** The generated `phpstan.neon` analyses `app/`,
  `database/`, and `routes/`; `config/` and `bootstrap/app.php` are no longer
  included, because Laravel's own shipped config files fail level 7 with
  strict-rules. Widen `paths` by hand when you want them covered.
- **Coverage is no longer enforced unless you ask for it.** The coverage prompt
  defaults to blank (placeholder `e.g. 80`) and `--no-interaction` records no
  threshold, so a fresh project is not red merely for having few tests.
- **The manifest moved to `.claude/claude-kit.json`** and now records every
  selection plus a sha256 per published file.
  **Migration:** nothing to do — both runtime scripts read the new path and fall
  back to the legacy root `.claude-kit.json`, so a project installed with 0.2.x
  keeps working. Re-running the installer writes the manifest at the new path;
  the old file is then redundant and can be deleted.
- **`.claude/settings.json` is deep-merged, never clobbered.** New keys are
  added, lists are unioned (so re-running never duplicates a hook), and on a
  scalar conflict your value wins and is reported as kept.
- **`CLAUDE.md` is short and delimited.** It carries project identity, the gate,
  the feature-doc rule, and an index of the published rules, wrapped in
  `claude-kit:begin` / `claude-kit:end` markers — an existing CLAUDE.md gains the
  managed block instead of being overwritten.
- **Larastan is now optional** (`phpstan.larastan`); declining it also drops the
  Carbon extension from the generated `phpstan.neon`.
- The interactive flow is grouped into titled sections, and the PHPStan level is
  asked exactly once for both the config and the hook.
- The Actions rule now states outright that an action exposes exactly one public
  method, `__invoke()`, and that this overrides any example elsewhere — including
  the bundled `laravel-best-practices` skill, whose example uses `execute()`.

### Fixed

- Appending the managed block to a CLAUDE.md that already has content no longer
  introduces a second top-level heading: the block's own `# Title` is dropped, so
  its `##` sections sit under the file's existing title. A CLAUDE.md that holds
  nothing but the block keeps its title.
- The PHPStan hook's warn mode now counts issues from PHPStan's JSON output
  rather than by matching text, so the reported number is right even when another
  package (such as `laravel/pao`, shipped with the Laravel 13 skeleton) replaces
  the default error formatter. It falls back to the previous text match if the
  output is not JSON.

## [0.2.2] - 2026-07-05

### Added

- `AGENTS.md` (the tool-agnostic agents.md standard) and `llms.txt`
  (llmstxt.org) so AI coding agents and LLM-powered search can discover and
  correctly use the package.
- A repository banner and richer README (badges, "who it's for").

### Changed

- Expanded Composer `keywords` and sharpened the `description` for Packagist and
  search discoverability.
- License badge now reads GitHub's detected license (reliable MIT) instead of
  the flaky Packagist license badge.
- Corrected the `LICENSE` copyright holder.

## [0.2.1] - 2026-07-05

### Fixed

- Added test coverage for the interactive installer, the skills.sh integration,
  and `TestTool`, so the 80% CI coverage gate passes on a tagged release.
- The repo's own gate (`bin/quality-checks.sh`) now runs Pest with the coverage
  gate, matching CI.

## [0.2.0] - 2026-07-05

### Added

- **Fully interactive installer.** `claude-kit:install` now asks what you want
  instead of scaffolding a fixed set: Pint on/off; PHPStan on/off + level (0–9) +
  strict-rules on/off; a test gate on/off + runner (Pest/PHPUnit) + coverage
  minimum + architecture tests; which hooks enforce the gate (Claude Stop, git
  pre-commit, feature-docs); which bundled skills to install; and which extras
  (CLAUDE.md, feature docs, editorconfig, MCP, CI).
- **skills.sh integration.** Optionally search and install additional skills via
  the `npx skills find` / `npx skills add` CLI, guarded by an `npx` availability
  check.
- `.claude-kit.json` manifest recording the chosen test runner, coverage
  threshold, and feature-doc toggle; the runtime gate reads it.
- `InstallOptions` DTO and `TestTool` enum capturing the resolved configuration.

### Changed

- `phpstan.neon` is now **generated** with the chosen level and strict-rules
  toggle (instead of copied), so PHPStan config reflects your selection.
- `quality-checks.sh` is now selection-aware: Pint/PHPStan run when their config
  exists, tests run per the manifest (runner + coverage), and the frontend block
  is unchanged (script-presence based).
- `.claude/settings.json` is generated: the Stop hook and permission allowlist
  reflect the selected hooks and tools.

### Removed

- The `--parts` flag (replaced by interactive selection) and the static
  `phpstan.neon`/`settings.json` stubs and vendored `phpstan/base.neon` (now
  generated).

## [0.1.0] - 2026-07-05

### Added

- Initial release.
- `claude-kit:install` Artisan command that scaffolds a Laravel project for
  Claude Code, with interactive prompts and non-interactive flags
  (`--stack`, `--parts`, `--force`).
- Frontend stack detection (`StackDetector`) for Inertia + Vue, Inertia + React,
  Blade/Livewire, and API-only projects, driving stack-specific ESLint/Prettier/
  TypeScript config, npm scripts, devDependencies, and skills.
- Referenced runtime machinery (auto-updating via `composer update`):
  `quality-checks.sh`, the Stop hook `stop-validate.sh`, and the PHPStan
  `base.neon` (level 7 + strict-rules + Larastan).
- Published, project-owned stubs: `CLAUDE.md`, `.claude/settings.json`, skills,
  `phpstan.neon`, `pint.json`, `tests/Arch/ArchTest.php`, the `.githooks/pre-commit`
  shim, feature-doc templates, `.editorconfig`, `.gitattributes`, and GitHub
  Actions workflows.
- Idempotent, non-destructive `composer.json` / `package.json` mergers.

[Unreleased]: https://github.com/mohamed-ashraf-elsaed/claude-kit/compare/v0.3.0...HEAD
[0.3.0]: https://github.com/mohamed-ashraf-elsaed/claude-kit/compare/v0.2.2...v0.3.0
[0.2.2]: https://github.com/mohamed-ashraf-elsaed/claude-kit/compare/v0.2.1...v0.2.2
[0.2.1]: https://github.com/mohamed-ashraf-elsaed/claude-kit/compare/v0.2.0...v0.2.1
[0.2.0]: https://github.com/mohamed-ashraf-elsaed/claude-kit/compare/v0.1.0...v0.2.0
[0.1.0]: https://github.com/mohamed-ashraf-elsaed/claude-kit/releases/tag/v0.1.0
