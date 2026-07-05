# Skills

claude-kit publishes a curated set of [Claude Code skills](https://docs.claude.com/en/docs/claude-code)
into `.claude/skills/`. Claude activates them automatically based on the work at
hand (or you can invoke one with `/<skill-name>`).

## What ships

Installed for **every** stack:

- **laravel-best-practices** — Laravel patterns: queries, security, validation,
  caching, queues, migrations, and more (one rule file per topic under `rules/`).
- **pest-testing** — writing and fixing Pest tests (feature/unit/browser,
  datasets, mocking, architecture tests).

Installed **per stack** (see [Frontend stacks](Frontend-Stacks)):

- **inertia-vue-development** — Inertia v3 + Vue 3 client-side patterns.
- **wayfinder-development** — typed routes/actions for the frontend.
- **tailwindcss-development** — Tailwind utility patterns.

Optional (present in the package, install by editing your selection):

- **socialite-development**, **ai-sdk-development**.

## Customising

Everything under `.claude/skills/` is a plain Markdown bundle you own. Edit them,
delete the ones you don't want, or add your own project skills alongside them.

## Refreshing after an update

Skills are *copied* (not referenced), so `composer update` does not change them.
Pull the latest shipped versions with:

```bash
php artisan claude-kit:install --parts=claude --force
```
