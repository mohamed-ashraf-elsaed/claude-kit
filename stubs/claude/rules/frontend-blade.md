---
paths:
  - "resources/views/**/*.blade.php"
---

<!-- Published by claude-kit for the server-rendered Blade stack. Every
     convention below is a DRAFT: correct it to match the codebase, then delete
     the DRAFT markers. -->

# Frontend — server-rendered Blade

<!-- DRAFT -->
## Layout

- Page views mirror the route path (`resources/views/orders/show.blade.php`).
  Layouts in `resources/views/layouts/`, reusable pieces as Blade components in
  `resources/views/components/`.
- File names are kebab-case (`show-filtered.blade.php`).
- Prefer a Blade component (`<x-alert>`) over `@include`: a component has an
  explicit, documented set of props; an include silently inherits scope.

<!-- DRAFT -->
## What a view may do

- Render data it was given. That is all.
- **No queries in a view** — no `Model::` calls, no `->load()`, no `count()` that
  hits the database. The controller or view model passes exactly what is needed,
  with relations already eager-loaded.
- No business rules. A branch that decides *policy* belongs in PHP; a branch that
  decides *markup* belongs here.
- No inline `<script>` or `<style>` blocks. Pass data to JavaScript with
  `data-*` attributes or `@json`, and keep behaviour in a bundled asset.

<!-- DRAFT -->
## Escaping and safety

- `{{ }}` everywhere. `{!! !!}` requires a comment naming the source and why it
  is trusted.
- Never interpolate user input into an attribute that becomes a URL or a
  JavaScript expression.
- Forms include `@csrf`, and `@method` where the verb is not GET or POST.

<!-- DRAFT -->
## The gate

Prettier formats Blade and asset files as part of the shared quality gate, so an
unformatted template blocks the commit, the turn, and CI.
