# Configuration

Most of what claude-kit installs is plain, editable config in your repo. Tune it
directly.

## Environment toggles

| Variable | Default | Effect |
| --- | --- | --- |
| `CLAUDE_KIT_MIN_COVERAGE` | `80` | Minimum Pest coverage percentage the gate enforces. |
| `CLAUDE_KIT_FEATURE_DOCS` | `1` | Set to `0` to disable the "watched code needs a feature doc" Stop-hook gate. |

Set these in your shell, CI environment, or a hook wrapper.

## Customising the output

- **Rules** — edit `CLAUDE.md`. Fill in the product/integration/deployment
  `TODO`s. This file is yours; the package never overwrites it without `--force`.
- **PHPStan** — `phpstan.neon` includes the vendored base (level 7 + strict-rules).
  Add `paths`, `ignoreErrors`, or raise the level in your local file.
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
