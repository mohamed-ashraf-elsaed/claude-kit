# Features

This directory is the **single source of truth** for every feature and bug fix
in this project. It is a hard project rule (see `CLAUDE.md` → "Feature
documentation") and is **enforced by the Stop quality-gate hook**
(`vendor/mohamed-ashraf-elsaed/claude-kit/runtime/hooks/stop-validate.sh`): if backend/frontend
code changes without a corresponding update here, the turn is blocked.

## Layout

```
features/
  _TEMPLATE/          # canonical templates — copy these, do not edit in place
    FEATURE.md
    DEPLOY.md
  <feature-name>/     # one folder per feature, kebab-case (e.g. authentication)
    FEATURE.md        # what it is, business logic, what it calls, responses
    DEPLOY.md         # deploy actions required (migrations, seeders, env, ...)
```

## When to write / update

- **New feature** → create `features/<feature-name>/` with both `FEATURE.md`
  and `DEPLOY.md`, fully filled in, once the work is finalized.
- **Bug fix or change to an existing feature** → update that feature's
  `FEATURE.md` **Change Log** section (and `DEPLOY.md` if the fix needs a
  migration, seeder, or other deploy step).
- Every feature folder always contains **both** files. If a feature needs no
  deploy steps, `DEPLOY.md` must say so explicitly ("No deploy steps required").

## What "watched" means

A feature doc is required when code changes under: `app/`, `database/`,
`routes/`, or `resources/js/`. Changes limited to `config/`, `tests/`, or docs
do not trigger the requirement.

> Set `CLAUDE_KIT_FEATURE_DOCS=0` in your environment to disable this gate.
