---
name: codemap
description: Create or refresh CODEMAP.md — a short, accurate map of where things live in this repository and how a request flows through it. Use when asked for a codemap, a repo map, an architecture overview, or when CODEMAP.md is stale.
---

Produce `CODEMAP.md` at the repository root: the file a new developer reads to
find things. It is a map, not documentation of behaviour.

## 1. Read before writing

Do not describe the layout from convention. Establish it:

- The directory tree of `app/`, `routes/`, `resources/`, `database/`, `tests/`
  (directories and notable files, not every file).
- The route files and what each one is for.
- The entry points: HTTP controllers, console commands, queued jobs, event
  listeners, scheduled tasks.
- The layers actually in use — only the directories that exist.
- Composer and npm scripts, and what the quality gate runs.
- External integrations: packages and services the code actually calls.

## 2. Write CODEMAP.md with exactly these sections

1. **What this application is** — two sentences, from the code, not marketing.
2. **Directory map** — a fenced tree of the real directories, one trailing
   comment per entry saying what belongs there. Only what exists.
3. **Request lifecycle** — for one representative read and one representative
   write: route → middleware → controller → the layers it delegates to →
   persistence → response. Cite real class names.
4. **Entry points** — a table: type (HTTP / command / job / listener / schedule),
   where it is registered, and what it starts.
5. **Data model** — the main tables and how they relate, plus the tenancy or
   ownership key if there is one.
6. **Conventions in force** — the rule files under `.claude/rules/` and what each
   covers, in one line each.
7. **Commands** — how to install, run, test, and check the project.
8. **External dependencies** — service, what it is used for, where the client
   lives.

## 3. Rules for the content

- Every path, class, and command you name must exist. Verify each one; a wrong
  path in a codemap is worse than a missing section.
- Names and paths, not prose. No behaviour documentation — that lives in the
  feature docs.
- Keep it under 200 lines. If a section wants to grow, it belongs in its own doc.
- Do not invent a planned structure or an aspirational layer. Map what is there
  today.
- Mark anything you could not determine as `TODO:` with the specific question,
  rather than guessing.

## 4. When refreshing an existing CODEMAP.md

- Keep its section order and any hand-written notes that are still true.
- Correct what moved, add what is new, delete what no longer exists.
- Report the differences you applied, so the reader can see what drifted.
