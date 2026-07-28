---
name: commit
description: Stage and commit the current work with a message that follows this project's convention. Invoke with /commit.
disable-model-invocation: true
allowed-tools: Bash(git status:*) Bash(git diff:*) Bash(git log:*) Bash(git add:*) Bash(git commit:*)
---

Commit the work that is currently in the tree. Do exactly these steps, in order.

## 1. See what is actually there

Run all three — never commit from memory of what you changed:

```bash
git status --short
git diff --stat
git diff
```

Also read the last few subject lines to match the existing voice:

```bash
git log --oneline -10
```

## 2. Refuse to commit these

Stop and tell the user instead of committing when the diff contains:

- a credential, token, private key, or password — including inside a fixture,
  a test, or a comment;
- `.env`, a database dump, a `*.sqlite` file, `vendor/`, `node_modules/`, or a
  build artefact that should be ignored;
- a debugging leftover: `dd(`, `dump(`, `var_dump(`, `console.log(`, `ray(`, a
  commented-out block, or a skipped test that was not skipped on purpose;
- unrelated changes from someone else's work in progress.

## 3. Stage deliberately

- One logical change per commit. If the diff does two unrelated things, stage and
  commit them separately.
- Stage the specific paths (`git add path/to/file`). Use `git add -A` only after
  `git status` shows nothing unrelated.
- Never stage a file you have not read in this session's diff.

## 4. Write the message

{{COMMIT_CONVENTION}}

Whatever the format, the message says **what changed and why**, in the
imperative, wrapped at 72 characters, with no trailing period on the subject. It
never says "as requested", never names the tool that wrote it, and never
describes the process ("fixed the thing I broke earlier").

## 5. Commit

```bash
git commit -m "<subject>" -m "<body, if the why is not obvious>"
```

- The pre-commit quality gate runs here. If it fails, **fix the reported
  problems and commit again**. Never pass `--no-verify`.
- Do not `git push`, `git commit --amend`, or rebase unless the user asked for it
  in this turn.
- Report the resulting commit hash and subject in one line.
