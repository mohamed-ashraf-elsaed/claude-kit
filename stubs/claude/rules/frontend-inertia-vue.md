---
paths:
  - "resources/js/**/*.vue"
  - "resources/js/**/*.ts"
---

<!-- Published by claude-kit for the Inertia + Vue stack. Inertia and Tailwind
     API details live in the bundled skills; this file is only this project's
     conventions. Every convention below is a DRAFT: correct it to match the
     codebase, then delete the DRAFT markers. -->

# Frontend — Inertia + Vue 3 + TypeScript

<!-- DRAFT -->
## Layout

- Pages in `resources/js/pages/`, mirroring the route path
  (`pages/settings/Profile.vue`). Reusable components in
  `resources/js/components/`, layouts in `resources/js/layouts/`,
  composables in `resources/js/composables/` named `useThing.ts`.
- Components are `PascalCase.vue`; composables and plain modules are
  `camelCase.ts`.
- Import through the `@/` alias, never a `../../` chain.

<!-- DRAFT -->
## Components

- `<script setup lang="ts">` in every component. No Options API, no
  `defineComponent`.
- Props and emits are declared with types, not runtime validators:
  `defineProps<{ user: User }>()`.
- A component has a single root element.
- Types shared with the backend live in `resources/js/types/`; a page never
  redeclares a shape another page already types.

<!-- DRAFT -->
## Data and navigation

- Server data reaches a page through Inertia props. Do not `fetch`/`axios` a
  Laravel route that could have been a prop or an Inertia visit.
- Never hardcode a URL string. Use the generated route helpers, or a named
  route.
- Forms go through Inertia's form handling, and validation errors come from the
  server response — no duplicated client-side rule set as the source of truth.

<!-- DRAFT -->
## The gate

`eslint`, `prettier`, and `vue-tsc --noEmit` all run in the shared quality gate,
so a type error or an unformatted file blocks the commit, the turn, and CI. Fix
the cause; do not add `// @ts-ignore` or an eslint-disable comment to get past
it.
