---
paths:
  - "app/**/*.php"
---

<!-- Published by claude-kit because this project selected Value Objects. Every
     convention below is a DRAFT: check it against how this codebase actually
     works, correct it, then delete the DRAFT markers. -->

# Value Objects (`app/ValueObjects`)

Small immutable types that make an invalid value impossible to hold — `Money`,
`EmailAddress`, `Percentage` — rather than passing a bare `int` or `string`.

<!-- DRAFT -->
## Shape

- `final readonly class` in `app/ValueObjects`, named for the concept. No
  suffix: `Money`, not `MoneyValueObject`.
- Wraps one concept. `Money` holds an integer amount in minor units plus a
  currency; it does not also hold a formatted string.
- Amounts of money are integers in minor units. Never a float.

<!-- DRAFT -->
## Invariants

- The constructor enforces every rule and throws `InvalidArgumentException` when
  it cannot: a negative quantity, a malformed address, a percentage above 100.
- Once constructed, the object is valid everywhere. No caller re-checks it.
- Guard in the constructor and return early; do not defer validation to a
  `validate()` method a caller must remember to call.

<!-- DRAFT -->
## Behaviour

- Operations return new instances: `$price->add($tax)`, `$rate->applyTo($base)`.
  Nothing mutates.
- Compare with an explicit `equals(self $other): bool`. Never `===` (identity)
  and never `==`.
- Behaviour that belongs to the concept lives here (`Money::format()`), so it is
  not re-implemented per call site.

<!-- DRAFT -->
## Persistence

- Convert at the model boundary with a custom Eloquent cast, so the rest of the
  app never sees the primitive.
- Value objects have no identity and no ID; anything that needs one is a model.
