# Deploy: <Feature Name>

> Copy this file to `features/<feature-name>/DEPLOY.md`. This file must describe
> EVERY action required to deploy this feature beyond merging code, in the order
> they must run. It must be readable by a human AND runnable by Claude.
>
> If the feature needs no deploy actions, state exactly: **"No deploy steps
> required."** and delete the sections below.

| | |
| --- | --- |
| **Feature** | `features/<feature-name>/FEATURE.md` |
| **Last updated** | <YYYY-MM-DD> |
| **Requires downtime?** | <no / yes — why> |
| **Reversible?** | <yes / no — see Rollback> |

## 1. Prerequisites

> New env vars, config, third-party keys, or infra (queues, storage disks,
> vector stores) that must exist before deploying. List each with where it goes.

```dotenv
# Example — add to .env / deployment secrets:
# SOME_KEY=
```

## 2. Commands (run in this exact order)

> Copy-pasteable, idempotent where possible. These are the literal commands to
> run on deploy.

```bash
php artisan migrate --force
# php artisan db:seed --class=Database\\Seeders\\<Seeder> --force
# php artisan queue:restart
```

## 3. Migrations

> List each migration this feature adds and what it changes.

- `database/migrations/<file>.php` — <what it does>

## 4. Seeders / data backfill

> Any seeders or one-off backfill scripts, and whether they are safe to re-run.

- <none / `Database\Seeders\<Seeder>`>

## 5. Post-deploy verification

> How to confirm the deploy succeeded (a query, a route to hit, a log to check).

## 6. Rollback

> How to safely undo this deploy if something goes wrong.

```bash
# e.g. php artisan migrate:rollback --step=1
```
