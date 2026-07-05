# Architecture

claude-kit is a small, focused Composer package. This page explains how it is
built so contributors change the right thing in the right place.

## The hybrid model

The package splits everything it ships into two categories:

- **`runtime/` — referenced.** Host projects call these files in place at
  `vendor/mohamed-ashraf-elsaed/claude-kit/runtime/…`. Because they live in
  `vendor/`, a `composer update` propagates fixes to every consumer. Treat them
  as a public API: `quality-checks.sh`, `hooks/stop-validate.sh`, and
  `phpstan/base.neon`. The base config's relative `includes` (`../../../../…`)
  assume the exact vendor depth — do not move the file.
- **`stubs/` — published.** Copied into the host once at install time. Consumers
  own their copy; updates only reach them via `install --force`. So *machinery*
  goes in `runtime/`, *content/opinion* goes in `stubs/`.

## Components

```
src/
  ClaudeKitServiceProvider.php   Registers the command (Laravel package discovery)
  Commands/InstallCommand.php    Thin: resolves stack + parts, prints report, wires git hook
  Support/
    FrontendStack.php            Enum — the ONE place per-stack differences live
    Part.php                     Enum — installable parts
    StackDetector.php            Reads composer.json + package.json → FrontendStack
    Installer.php                Pure filesystem scaffolder (no shell-outs)
    PackageJsonMerger.php        Idempotent, non-destructive package.json merge
    ComposerJsonMerger.php       Idempotent, non-destructive composer.json merge
```

- **`InstallCommand`** does no file work itself — it resolves inputs and
  delegates to `Installer`. This keeps it testable and thin.
- **`Installer`** is pure I/O: `copyStub`/`copyTree` apply `{{PLACEHOLDER}}`
  replacements, skip existing files unless `--force`, and return a report of
  created/overwritten/skipped paths. Frontend and composer changes go through the
  mergers.
- **Mergers** preserve existing keys (existing wins) and are idempotent —
  re-running produces byte-identical output.
- **`FrontendStack`** centralises per-stack skills, npm scripts, devDependencies,
  the stub directory, and the CLAUDE.md prose. Add a stack here + a
  `stubs/frontend/<dir>/` directory; nothing else needs to change.

## Testing

- **Unit** tests exercise `StackDetector`, the mergers, and `Installer` against
  temp directories (`temp_project()` / `stubs_path()` helpers in `tests/Pest.php`).
- **Feature** tests run `claude-kit:install` through Testbench with the base path
  pointed at a temp directory.
- The suite asserts per-stack output, idempotency, and skip/force behavior.

## Dogfooding

The repo runs its own gate (`bin/quality-checks.sh`) via a pre-commit hook and a
Claude Stop hook, including the changelog rule — the same philosophy it ships.
