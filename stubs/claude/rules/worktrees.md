<!-- Published by claude-kit. Always loaded — no `paths` frontmatter, because it
     describes a workflow rather than a class of files. Every convention below is
     a DRAFT: this is a starting convention, not your team's — correct it, then
     delete the DRAFT markers. -->

# Parallel work with git worktrees

Two features in flight at once get two worktrees, not one branch you keep
stashing. Each worktree is a full checkout sharing this repository's history.

<!-- DRAFT -->
## Layout

- One worktree per branch, created from an up-to-date default branch:
  ```bash
  git fetch origin
  git worktree add ../<repo>-<branch> -b <branch> origin/<default-branch>
  ```
- Keep worktrees as siblings of the main checkout (`../<repo>-<branch>`), never
  nested inside it — a nested checkout ends up in Vite globs, PHPStan paths, and
  test discovery.
- One task per worktree. Do not start a second feature in a worktree that
  already has uncommitted work.

<!-- DRAFT -->
## Setting one up

- `composer install` and `npm install` per worktree: `vendor/` and
  `node_modules/` are not shared.
- Copy `.env` from the main checkout, then change anything that must be unique —
  the app URL/port, the queue and cache prefixes, and the log path.
- Use a separate database per worktree, or a separate table prefix. Two
  worktrees running migrations against one database will corrupt each other's
  schema state.
- Run the quality gate inside the worktree you changed. It reads the config of
  the checkout it runs in.

<!-- DRAFT -->
## Finishing up

- Push the branch and open the pull request from the worktree.
- Remove the worktree once merged, then prune:
  ```bash
  git worktree remove ../<repo>-<branch>
  git worktree prune
  ```
- Never delete a worktree directory by hand — that leaves a stale entry in
  `.git/worktrees` that breaks the next `git worktree add` for the same path.

<!-- DRAFT -->
## What not to share between worktrees

Migrations against a shared database, a shared queue connection consumed by two
`queue:work` processes, the same Redis/cache prefix, and the same storage disk
path. Each of these will make one worktree's run affect the other's.
