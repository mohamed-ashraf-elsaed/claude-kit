# 🔧 Configuration

Almost everything claude-kit installs is plain, editable config in your repo. Tune it directly.

## Environment toggles

| Variable | Default | Effect |
| --- | --- | --- |
| `CLAUDE_KIT_MIN_COVERAGE` | `80` | Minimum Pest coverage percentage the gate enforces. |
| `CLAUDE_KIT_FEATURE_DOCS` | `1` | Set to `0` to disable the "watched code needs a feature doc" Stop-hook gate. |

Set these in your shell, CI environment, or a hook wrapper.

## The `.claude-kit.json` manifest

The installer writes a small manifest recording your choices; the runtime gate reads it to know what to run.

```json
{
    "stack": "inertia-vue",
    "tests": { "enabled": true, "tool": "pest", "coverage_min": 80 },
    "hooks": { "feature_docs": true }
}
```

| Key | Meaning |
| --- | --- |
| `tests.enabled` | Whether the gate runs tests at all. |
| `tests.tool` | `pest` or `phpunit`. |
| `tests.coverage_min` | Coverage threshold, or `null` to not enforce. |
| `hooks.feature_docs` | Whether the Stop hook requires a feature doc for watched code (env var overrides). |

> [!NOTE]
> Pint and PHPStan run whenever `pint.json` / `phpstan.neon` exist — so removing one of those files disables that check. Edit the manifest to change test behavior without re-running the installer.

## Customising the output

<details>
<summary>What each file is and how to change it</summary>

- **Rules** — edit `CLAUDE.md`; fill in the product/integration/deployment `TODO`s. Never overwritten without `--force`.
- **PHPStan** — `phpstan.neon` is generated with the level and strict toggle you chose. Change the level, add `paths` / `ignoreErrors` freely.
- **Pint** — `pint.json` uses the Laravel preset plus a few strict rules.
- **ESLint / Prettier / TypeScript** — the stack config files are yours to edit.
- **Skills** — everything under `.claude/skills/` is editable; add your own.
- **Permissions & hooks** — `.claude/settings.json` holds the Stop-hook wiring and a permission allowlist. Extend it for your project's commands.
</details>

## The pre-commit hook

claude-kit sets `git config core.hooksPath .githooks` during install (composer's `post-autoload-dump` re-runs it).

```bash
git config --unset core.hooksPath   # disable locally
git commit --no-verify              # bypass a single commit
```

## Keeping machinery current

`quality-checks.sh` and the Stop hook live under `vendor/…/runtime/` and update with:

```bash
composer update mohamed-ashraf-elsaed/claude-kit
```

To refresh the copied content (skills, rules), re-run `php artisan claude-kit:install --force` for the parts you want. See **[Upgrading](Upgrading)**.

---
<sub>[← Usage](Usage) · 🏠 [Home](Home) · [Frontend stacks →](Frontend-Stacks)</sub>
