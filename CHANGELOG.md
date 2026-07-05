# Changelog

All notable changes to `claude-kit` are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

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

[Unreleased]: https://github.com/mohamed-ashraf-elsaed/claude-kit/compare/v0.2.2...HEAD
[0.2.2]: https://github.com/mohamed-ashraf-elsaed/claude-kit/compare/v0.2.1...v0.2.2
[0.2.1]: https://github.com/mohamed-ashraf-elsaed/claude-kit/compare/v0.2.0...v0.2.1
[0.2.0]: https://github.com/mohamed-ashraf-elsaed/claude-kit/compare/v0.1.0...v0.2.0
[0.1.0]: https://github.com/mohamed-ashraf-elsaed/claude-kit/releases/tag/v0.1.0
