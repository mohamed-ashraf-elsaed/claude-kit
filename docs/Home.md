# claude-kit wiki

**claude-kit** sets up any Laravel project for [Claude Code](https://claude.com/claude-code)
with one command: engineering rules, a Stop-hook + pre-commit quality gate,
skills, PHPStan/Pint/Pest, feature-doc scaffolding, and stack-aware frontend
tooling.

```bash
composer require --dev mohamed-ashraf-elsaed/claude-kit
php artisan claude-kit:install
```

## Guides

- **[Installation](Installation)** — install and run for the first time
- **[Usage](Usage)** — the command, flags, and re-running
- **[Configuration](Configuration)** — env toggles and customising the output
- **[Frontend stacks](Frontend-Stacks)** — Vue / React / Blade / API-only
- **[Quality gate](Quality-Gate)** — Pint, PHPStan, Pest, the hooks
- **[Skills](Skills)** — which skills ship and how they activate
- **[Architecture](Architecture)** — how the package is built (hybrid model)
- **[Publishing](Publishing)** — releasing and Packagist
- **[Upgrading](Upgrading)** — moving between versions
- **[FAQ](FAQ)** — common questions

## Concepts in one minute

- **Hybrid updates:** machinery lives in `vendor/…/runtime/` and auto-updates via
  `composer update`; content (rules, skills, configs) is copied into your repo so
  you own it.
- **One gate, everywhere:** the pre-commit hook, Claude's Stop hook, and CI all
  run the same `quality-checks.sh`.
- **Stack-aware:** the installer tailors the frontend tooling and skills to your
  project's stack.
