---
paths:
  - "app/**/*.php"
---

<!-- Published by claude-kit because this project selected the Repositories
     layer. Every convention below is a DRAFT: check it against how this
     codebase actually works, correct it, then delete the DRAFT markers. -->

# Repositories (`app/Repositories`)

Where persistence queries are written down once and given a name, so the same
query is not rebuilt in three controllers.

<!-- DRAFT -->
## Shape

- One repository per aggregate root, `final`, suffixed `Repository`
  (`OrderRepository`), in `app/Repositories`.
- Methods are named for the question they answer — `openForCustomer()`,
  `awaitingFulfilment()` — never for the mechanism (`getWhere()`, `findBy()`,
  `all(array $filters)`).
- Return Eloquent models, Eloquent collections, or paginators. Repositories do
  not invent their own hydration or return arrays of arrays.

<!-- DRAFT -->
## What belongs here

- Any query that is used in more than one place, or that is more than a
  `find()`/`create()` on a single table.
- The eager loading a query needs (`with(...)`), so callers cannot trigger an
  N+1 by forgetting it.
- Write operations that must stay consistent for the aggregate
  (`markFulfilled()`), not a generic `update(array $attributes)`.

<!-- DRAFT -->
## What does not

- No HTTP, no formatting, no authorization checks — those belong to the caller.
- No business decisions: a repository answers questions, it does not choose what
  to do with the answer.
- Do not add a repository whose methods only forward to Eloquent one-for-one.
  Where a model or a query scope already reads clearly, use it directly.

<!-- DRAFT -->
## Consumption

- Services and actions depend on the repository through the constructor.
- Controllers use a repository directly only for a plain read with no business
  logic attached; anything else goes through a service.
