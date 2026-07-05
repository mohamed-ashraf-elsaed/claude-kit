# Configuration

Most of what claude-kit installs is plain, editable config in your repo. Tune it
directly.

## Environment toggles

| Variable | Default | Effect |
| --- | --- | --- |
| `CLAUDE_KIT_MIN_COVERAGE` | `80` | Minimum Pest coverage percentage the gate enforces. |
| `CLAUDE_KIT_FEATURE_DOCS` | `1` | Set to `0` to disable the "watched code needs a feature doc" Stop-hook gate. |

Set these in your shell, CI environment, or a hook wrapper.

## The `.claude-kit.json` manifest

The installer writes a small manifest recording your choices; the runtime gate
reads it to know what to run:

```json
{
    "stack": "inertia-vue",
    "tests": { "enabled": true, "tool": "pest", "coverage_min": 80 },
    "hooks": { "feature_docs": true }
}
```

- `tests.enabled` / `tests.tool` / `tests.coverage_min` — whether the gate runs
  tests, with which runner, and the coverage threshold (`null` = don't enforce).
- `hooks.feature_docs` — whether the Stop hook requires a feature doc for watched
  code changes (the `CLAUDE_KIT_FEATURE_DOCS` env var overrides it).

Edit this file to change the gate's behavior without re-running the installer.
Pint and PHPStan run whenever `pint.json` / `phpstan.neon` exist, so removing
those files disables them.

## Customising the output

- **Rules** — edit `CLAUDE.md`. Fill in the product/integration/deployment
  `TODO`s. This file is yours; the package never overwrites it without `--force`.
- **PHPStan** — `phpstan.neon` is generated at install time with the level and
  strict-rules toggle you chose. Edit it freely: change the level, add `paths`,
  `ignoreErrors`, etc.
- **Pint** — `pint.json` uses the Laravel preset plus a few strict rules. Adjust freely.
- **ESLint / Prettier / TypeScript** — the stack config files are yours to edit.
- **Skills** — everything under `.claude/skills/` is editable; add your own.
- **Permissions & hooks** — `.claude/settings.json` holds the Stop-hook wiring and
  a permission allowlist. Extend the allowlist for your project's commands.

## The pre-commit hook

`claude-kit` sets `git config core.hooksPath .githooks` during install (composer's
`post-autoload-dump` re-runs it). To disable locally:

```bash
git config --unset core.hooksPath
```

Bypass a single commit with `git commit --no-verify`.

## Keeping machinery current

`quality-checks.sh`, the Stop hook, and `phpstan/base.neon` live under
`vendor/…/runtime/` and update with `composer update mohamed-ashraf-elsaed/claude-kit`.
To refresh the copied content (skills, rules), re-run
`php artisan claude-kit:install --force` for the relevant parts.
