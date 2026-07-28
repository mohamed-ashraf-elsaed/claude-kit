---
paths:
  - "app/Livewire/**/*.php"
  - "resources/views/**/*.blade.php"
---

<!-- Published by claude-kit for the Livewire + Blade stack. These are
     claude-kit's opinionated defaults — adapt them to match the codebase. -->

# Frontend — Livewire + Blade

## Layout

- Components in `app/Livewire/`, their views in `resources/views/livewire/`,
  matching names (`app/Livewire/Orders/Table.php` →
  `resources/views/livewire/orders/table.blade.php`).
- Component classes are `PascalCase`; Blade files are kebab-case.
- Shared markup goes in Blade components (`resources/views/components/`), not a
  Livewire component — Livewire is for interactivity, not for reuse.

## Component discipline

- Public properties are the component's state and are all explicitly typed. Keep
  them to what the view renders; anything else is computed.
- Use computed properties for derived values so they are not recalculated on
  every hydration.
- Never make an Eloquent model a public property with unfiltered mass
  assignment; expose the specific fields the form binds to, and authorize in the
  action method.
- Every public method reachable from the browser re-checks authorization. A
  hidden button is not a permission check.
- Validate in the component with a rules array or a form object — not in the
  Blade view.

## Rendering and performance

- No queries in Blade. A view receives what it renders; the component loads it,
  eager-loading the relations it will touch.
- `wire:key` on every item in a loop of components.
- Debounce text inputs that trigger a server round trip
  (`wire:model.live.debounce.300ms`), or bind deferred by default.
- A page's initial data comes from a controller or the component's `mount()`, not
  from a request the browser makes after paint.

## The gate

Prettier formats Blade and asset files as part of the shared quality gate, so an
unformatted template blocks the commit, the turn, and CI.
