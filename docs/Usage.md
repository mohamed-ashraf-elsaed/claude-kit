# Usage

## The command

```bash
php artisan claude-kit:install [--stack=] [--parts=] [--force]
```

| Option | Description |
| --- | --- |
| `--stack=` | `inertia-vue`, `inertia-react`, `blade`, or `none`. Auto-detected when omitted. |
| `--parts=` | Comma list of `claude,rules,quality,frontend,docs,ci`. All when omitted. |
| `--force` | Overwrite files that already exist (otherwise they are skipped). |

Run with no options for the interactive experience: it detects your stack,
confirms it, and lets you multi-select the parts.

## Parts

| Part | Installs |
| --- | --- |
| `claude` | `.claude/settings.json`, stack skills, `.mcp.json` |
| `rules` | `CLAUDE.md` (generic engineering rules with placeholders) |
| `quality` | `phpstan.neon`, `pint.json`, `tests/Arch/ArchTest.php`, `.githooks/pre-commit`, composer scripts |
| `frontend` | ESLint/Prettier/TS config + merged `package.json` (skipped for `none`) |
| `docs` | `features/_TEMPLATE/`, `features/README.md`, `.editorconfig`, `.gitattributes` |
| `ci` | `.github/workflows/tests.yml` and `lint.yml` |

## Examples

```bash
# React project, only the AI config + quality gate + frontend tooling
php artisan claude-kit:install --stack=inertia-react --parts=claude,quality,frontend

# API-only service — no frontend tooling is written
php artisan claude-kit:install --stack=none

# Refresh the skills/rules after upgrading the package
php artisan claude-kit:install --parts=claude,rules --force
```

## Re-running & idempotency

Re-running is safe. Files that exist are skipped unless `--force`. `composer.json`
and `package.json` are merged — your existing scripts and dependencies are never
overwritten, and running twice changes nothing.

## After install

1. `composer install` — installs Pint/PHPStan/Pest/Boost and wires the pre-commit hook.
2. `npm install` — if a frontend stack was set up.
3. Open `CLAUDE.md` and fill in the `TODO` placeholders (product context,
   integrations, deployment).
