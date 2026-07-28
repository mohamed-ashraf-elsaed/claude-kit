---
paths:
  - "app/**/*.php"
---

<!-- Published by claude-kit because this project selected Data Objects (DTOs).
     Every convention below is a DRAFT: check it against how this codebase
     actually works, correct it, then delete the DRAFT markers. -->

# Data Objects (`app/Data`)

Typed structures that carry data across a boundary, so a signature says what it
receives instead of `array $data`.

<!-- DRAFT -->
## Shape

- `final readonly class`, suffixed `Data` (`CheckoutData`), in `app/Data`.
- Every property is promoted in the constructor and explicitly typed. No
  `mixed`. An `array` property carries a PHPDoc shape:
  `/** @var list<array{sku: string, qty: int}> */`.
- Nullable only when the absence of the value is meaningful — not as a way to
  avoid deciding.

<!-- DRAFT -->
## Construction

- Named constructors describe the source: `fromRequest()`, `fromModel()`,
  `fromApiPayload()`. Each one is responsible for its own mapping.
- No `make(array $attributes)` catch-all: it defeats the reason the class exists.
- A data object never validates input. Validation happened in the form request
  before it was built.

<!-- DRAFT -->
## Behaviour

- Mapping and derivation only: `toArray()`, `withQuantity()`, a computed total.
- No database access, no facades, no dispatching, no HTTP.
- Immutable: a change returns a new instance (`with*()`), it never mutates.

<!-- DRAFT -->
## Use

- Prefer a data object over an associative array whenever data crosses a layer
  boundary (controller → service, service → job, service → view model).
- A single scalar does not need one. Two or more related values that travel
  together do.
