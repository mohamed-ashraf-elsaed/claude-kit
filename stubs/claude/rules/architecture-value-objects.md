---
paths:
  - "app/**/*.php"
---

<!-- Published by claude-kit because this project selected Value Objects.
     These are claude-kit's opinionated defaults, not a description of your
     codebase: read them, adapt anything that does not match how you actually
     work, and delete what you do not want. -->

# Value Objects (`app/ValueObjects`)

Small immutable types that make an invalid value impossible to hold — `Money`,
`EmailAddress`, `Percentage` — rather than passing a bare `int` or `string`.

## Shape

- `final readonly class` in `app/ValueObjects`, named for the concept. No
  suffix: `Money`, not `MoneyValueObject`.
- Wraps one concept. `Money` holds an integer amount in minor units plus a
  currency; it does not also hold a formatted string.
- Amounts of money are integers in minor units. Never a float.

## Invariants

- The constructor enforces every rule and throws `InvalidArgumentException` when
  it cannot: a negative quantity, a malformed address, a percentage above 100.
- Once constructed, the object is valid everywhere. No caller re-checks it.
- Guard in the constructor and return early; do not defer validation to a
  `validate()` method a caller must remember to call.

## Behaviour

- Operations return new instances: `$price->add($tax)`, `$rate->applyTo($base)`.
  Nothing mutates.
- Compare with an explicit `equals(self $other): bool`. Never `===` (identity)
  and never `==`.
- Behaviour that belongs to the concept lives here (`Money::format()`), so it is
  not re-implemented per call site.

## Persistence

- Convert at the model boundary with a custom Eloquent cast, so the rest of the
  app never sees the primitive.
- Value objects have no identity and no ID; anything that needs one is a model.
