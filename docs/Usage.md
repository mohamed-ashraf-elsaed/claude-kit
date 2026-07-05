# Usage

## The command

```bash
php artisan claude-kit:install [--stack=] [--force] [--no-interaction]
```

| Option | Description |
| --- | --- |
| `--stack=` | `inertia-vue`, `inertia-react`, `blade`, or `none`. Auto-detected when omitted. |
| `--force` | Overwrite files that already exist (otherwise they are skipped). |
| `--no-interaction` | Skip the prompts and accept sensible defaults (good for CI). |

Run it with no options for the full interactive experience.

## The interactive flow

The installer walks you through every choice:

1. **Frontend stack** — detected and confirmed (Vue / React / Blade / API-only).
2. **Code style** — use Pint? (yes/no)
3. **Static analysis** — use PHPStan? → **level** (0–9) → **strict-rules**?
4. **Tests** — set up a test gate? → **runner** (Pest / PHPUnit) → **coverage
   minimum** (a number, or blank to not enforce) → **architecture tests**? (Pest only)
5. **Hooks** — which of these enforce the gate:
   - Claude Code Stop hook (runs the gate on every turn)
   - Git pre-commit hook
   - Feature-doc requirement (part of the Stop hook)
6. **Skills** — pick from the bundled skills, then optionally search
   [skills.sh](https://www.skills.sh) for more (`npx skills find` / `add`).
7. **Extras** — CLAUDE.md rules, feature-doc templates, `.editorconfig` +
   `.gitattributes`, Laravel Boost MCP, GitHub Actions workflows.

Your selections are written to `.claude-kit.json`, which the runtime gate reads
to know which tools to run, the test runner, and the coverage threshold.

## Defaults (`--no-interaction`)

Pint on; PHPStan on at level 7 with strict-rules; Pest with an 80% coverage gate
and architecture tests; all three hooks; the stack's default skills; and all
extras.

## Re-running & idempotency

Re-running is safe. Files that exist are skipped unless `--force`. `composer.json`
and `package.json` are merged — your existing scripts and dependencies are never
overwritten, and running twice changes nothing.

## After install

1. `composer install` — installs the selected tooling and (if chosen) wires the
   git pre-commit hook.
2. `npm install` — if a frontend stack was set up.
3. Open `CLAUDE.md` and fill in the `TODO` placeholders.
