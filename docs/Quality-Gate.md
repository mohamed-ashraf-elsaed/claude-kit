# The quality gate

One script — `vendor/mohamed-ashraf-elsaed/claude-kit/runtime/quality-checks.sh` —
is the single source of truth, invoked in three places so the bar is identical
everywhere:

1. **git pre-commit hook** (`.githooks/pre-commit`) — blocks bad commits.
2. **Claude Code Stop hook** (`.claude/settings.json`) — blocks a turn from
   finishing until the gate passes.
3. **CI** (`.github/workflows/*`) — blocks merges.

## What it runs

**PHP (based on what you installed):**

- **Pint** (`--test`) — runs when `pint.json` exists.
- **PHPStan** — runs when `phpstan.neon` exists; the level and strict-rules are
  whatever you chose at install (they live in that file). Must be zero errors.
- **Tests** — per `.claude-kit.json`: the chosen runner (Pest / PHPUnit) and, for
  Pest, `--coverage --min=<your threshold>` when a coverage driver is present
  (otherwise the suite runs and coverage is warned, not enforced).

**Frontend (only if `package.json` defines the scripts):**

- `lint:check` (ESLint), `format:check` (Prettier), `types:check` (`vue-tsc`/`tsc`).

This conditional design keeps one script correct across Vue, React, Blade, and
API-only projects.

## The Stop hook's feature-doc gate

Beyond the quality suite, the Stop hook requires that any change under `app/`,
`database/`, `routes/`, or `resources/js/` is accompanied by an added/updated doc
under `features/<name>/` (copy `features/_TEMPLATE/`). This keeps documentation
in lockstep with code. Disable with `CLAUDE_KIT_FEATURE_DOCS=0`.

## Coverage driver

The 80% gate needs `pcov` (fast) or Xdebug:

```bash
sudo apt install -y php8.4-pcov
```

Without a driver, the gate still runs the tests and prints a warning instead of
failing on coverage.

## Bypassing (when you must)

- Commits: `git commit --no-verify`
- Coverage threshold: set `CLAUDE_KIT_MIN_COVERAGE` lower (not recommended)

These are escape hatches, not defaults — CI still enforces the full gate.
