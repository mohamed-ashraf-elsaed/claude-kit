# Changelog

All notable changes to `claude-kit` are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

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

[Unreleased]: https://github.com/mohamed-ashraf-elsaed/claude-kit/compare/v0.1.0...HEAD
[0.1.0]: https://github.com/mohamed-ashraf-elsaed/claude-kit/releases/tag/v0.1.0
