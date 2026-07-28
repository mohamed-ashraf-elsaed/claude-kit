---
paths:
  - "resources/js/**/*.tsx"
  - "resources/js/**/*.ts"
---

<!-- Published by claude-kit for the Inertia + React stack. Inertia and Tailwind
     API details live in the bundled skills; this file is only this project's
     conventions. These are
     claude-kit's opinionated defaults — adapt them to match the codebase. -->

# Frontend — Inertia + React + TypeScript

## Layout

- Pages in `resources/js/pages/`, mirroring the route path
  (`pages/settings/profile.tsx`). Reusable components in
  `resources/js/components/`, layouts in `resources/js/layouts/`, hooks in
  `resources/js/hooks/` named `use-thing.ts`.
- Components are `PascalCase` exports; files follow the directory's existing
  casing convention — do not introduce a second one.
- Import through the `@/` alias, never a `../../` chain.

## Components

- Function components only, typed props via an explicit interface or type alias.
  No `React.FC`, no `any`, no `PropTypes`.
- Derive state instead of mirroring it in a second `useState`. An effect that
  only syncs one state value to another should not exist.
- Every `useEffect` has a complete dependency array; if the linter complains,
  fix the dependency, not the lint rule.
- Types shared with the backend live in `resources/js/types/`; a page never
  redeclares a shape another page already types.

## Data and navigation

- Server data reaches a page through Inertia props. Do not `fetch`/`axios` a
  Laravel route that could have been a prop or an Inertia visit.
- Never hardcode a URL string. Use the generated route helpers, or a named
  route.
- Forms go through Inertia's form handling, and validation errors come from the
  server response — no duplicated client-side rule set as the source of truth.

## The gate

`eslint` (including `react-hooks`), `prettier`, and `tsc --noEmit` all run in the
shared quality gate, so a type error or an unformatted file blocks the commit,
the turn, and CI. Fix the cause; do not add `// @ts-ignore` or an eslint-disable
comment to get past it.
