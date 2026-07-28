---
name: deploy
description: Run this project's deploy for the current branch, including the per-feature deploy steps. Invoke with /deploy.
disable-model-invocation: true
---

Deploy this project. This is a production action: work through the steps in
order and stop at the first thing that does not look right.

## 1. Before anything runs

- Confirm with the user **what** is being deployed and **where** (which
  environment). If either is unstated, ask — do not assume production.
- Confirm the working tree is clean and the branch is the one that should ship:
  ```bash
  git status --short
  git log --oneline -5
  ```
- Run the quality gate and require it to pass before deploying anything.

## 2. Collect the deploy steps for what is shipping

Every feature in this project documents its own deploy actions. Read the
`DEPLOY.md` of each feature included in this release and list, in run order:

- migrations to run,
- seeders or data backfills, and whether they are safe to re-run,
- new environment variables or config that must exist **before** the deploy,
- queue, cache, or scheduler restarts required afterwards.

If a feature in this release has no deploy documentation, stop and say so. Do not
infer the steps from the diff.

## 3. Deploy

{{DEPLOY_COMMAND_BLOCK}}

## 4. Verify

- Confirm the release is live: the app responds, the version or commit is the one
  you deployed.
- Confirm each migration applied, and that the queue workers picked up the new
  code.
- Check the log and the error tracker for new entries in the minutes after the
  deploy.
- Report what ran, what changed, and anything that still needs a human.

## 5. If it goes wrong

- Do not iterate on production. Follow the **Rollback** section of the relevant
  feature's `DEPLOY.md`.
- If there is no documented rollback for a destructive step, stop and escalate to
  the user with exactly what state the system is in.
- Never re-run a non-idempotent seeder or backfill "to see if it helps".
