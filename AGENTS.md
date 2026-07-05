# AGENTS.md

Guidance for AI coding agents (Claude Code, Cursor, GitHub Copilot, Windsurf, …)
working **on the claude-kit package**. This is the tool-agnostic companion to
[`CLAUDE.md`](CLAUDE.md) — read that for the full architecture and conventions.

## What this project is

A Composer dev package that sets up Laravel projects for Claude Code in one
command (`php artisan claude-kit:install`): engineering rules, a Stop-hook +
pre-commit quality gate, skills, PHPStan/Pint/Pest, and stack-aware frontend
tooling. Namespace `MohamedAshrafElsaed\ClaudeKit` (PSR-4, `src/`).

## Setup

```bash
composer install
```

## Build / test / lint

```bash
composer check          # the full gate: Pint + PHPStan (level 7, strict) + Pest
composer lint           # auto-fix code style
composer types:check    # PHPStan only
composer test:coverage  # Pest with the 80% coverage gate (needs pcov/Xdebug)
```

All must pass before finishing. CI runs the same across PHP 8.2–8.4.

## Conventions (non-negotiable)

- `declare(strict_types=1);` everywhere; explicit types on every signature.
- `final` classes; small, single-responsibility units; no dead or speculative code.
- New behavior ships with tests (Pest). Feature tests drive the installer against
  a temp directory and must be deterministic.
- **Every user-facing change adds an entry under `## [Unreleased]` in
  `CHANGELOG.md`** (enforced by the gate). Releases follow SemVer — see
  [`RELEASING.md`](RELEASING.md).
- Put *machinery* in `runtime/` (referenced from vendor/) and *content* in
  `stubs/` (copied into host projects). See [`CLAUDE.md`](CLAUDE.md).

## Where things live

- `src/` — the command and `Support/` classes (Installer, StackDetector, mergers).
- `runtime/` — the shared gate + Stop hook referenced from vendor/.
- `stubs/` — files published into host projects.
- `tests/` — Pest Unit + Feature (Testbench).
- `docs/` — the wiki pages.
