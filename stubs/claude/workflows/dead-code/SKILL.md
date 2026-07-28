---
name: dead-code
description: Find code that is genuinely unused — classes, methods, routes, config keys, migrations-era leftovers, frontend modules — prove it is unreachable, then remove it. Use when asked to clean up dead code, find unused code, or shrink a codebase.
---

Detect, then prove, then remove. Never delete on the strength of a search that
came back empty once.

## 1. Detect candidates

Cast a wide net first and collect suspects, do not delete anything yet.

- Classes and methods: search for the symbol name across `app/`, `routes/`,
  `config/`, `database/`, `tests/`, `resources/`.
- Routes: list them (`php artisan route:list`) and look for names no controller,
  view, JS module, or test references.
- Config and env: keys in `config/` never read via `config('...')`, and
  `.env.example` entries no config file reads.
- Frontend: modules, components, and exported helpers nothing imports.
- Obvious leftovers: commented-out blocks, `*_old`/`*_backup`/`*.bak` files,
  feature flags whose branch is permanently one way, and skipped tests.

## 2. Prove each candidate is unreachable

A symbol is only dead when **all** of these hold. Check each one explicitly:

- No static reference anywhere: including `tests/`, Blade/JS templates, and
  string references.
- Not reachable dynamically: not named in a string used by `app()->make()`,
  `resolve()`, `new $class`, `call_user_func`, a `config()` value, a route
  action string, a queued job payload, an event/listener map, a policy or
  observer registration, a service provider binding, or a migration.
- Not part of a public contract: not implementing an interface method, not
  overriding a framework method, not a magic method, not an Eloquent
  accessor/mutator/scope/relationship name used from a query string, not a
  Livewire/Inertia entry point, not an API resource field.
- Not a package extension point: not autoloaded by convention (commands,
  factories, seeders, policies discovered by name).

When any check is uncertain, keep the code and say why. A false positive here
breaks production; a false negative costs nothing.

## 3. Remove

- One coherent removal per commit, so a revert is surgical.
- Delete the code **and** everything that existed only for it: its tests, its
  factory, its config key, its route, its translation strings, its frontend
  import.
- Never comment code out as "removal". Delete it.
- Do not "clean up" surrounding code in the same pass — removal only.

## 4. Verify

- Run the full quality gate: style, static analysis, and the whole test suite.
  A dropped reference shows up as a static-analysis error or a failing test.
- Grep once more for the removed symbol names; the result must be empty.
- If a database column, table, or migration is involved, the change needs a
  migration and a deploy note — flag it, do not silently drop a column.

## 5. Report

List what was removed and, for each item, the one-line reason it was provably
unreachable. List separately anything you suspected but kept, and what would
settle it.
