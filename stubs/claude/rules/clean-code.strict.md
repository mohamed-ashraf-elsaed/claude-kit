---
paths:
  - "**/*.php"
---

<!-- Published by claude-kit: the STRICT variant of the clean-code rule. The
     other variants are `balanced` (limits with escape hatches) and `relaxed`
     (direction without thresholds). The numbers below are claude-kit's
     defaults — adjust them to your team's real standard. -->

# Clean code — strict

Limits in this file are hard. When a change cannot meet one, the change is wrong,
not the limit.

| Limit | Strict |
| --- | --- |
| Method body | 20 lines |
| Class body | 150 lines |
| Parameters | 3 |
| Nesting depth | 2 |
| `else` | not used |

## Size

- A method body is at most **20 lines**, excluding the signature and the closing
  brace. Extract a private method rather than exceeding it.
- A class is at most **150 lines**. A longer class has more than one
  responsibility; split it.
- A file holds exactly one class, interface, enum, or trait.

## Control flow

- **Guard clauses only.** Validate, then return or throw at the top. The happy
  path is never nested inside an `if`.
- **No `else`.** Rewrite as an early return, a `match`, or a polymorphic call.
- Nesting is at most **2 levels** inside a method body. A third level means the
  inner block is a method.
- `match` over `switch`. A `switch` on a type is a missing polymorphic call.

## Signatures

- At most **3 parameters**. Beyond that, pass one typed object.
- No boolean flag parameters. `publish()` and `publishAsDraft()` beat
  `publish(bool $draft)`.
- Every parameter, property, and return value is explicitly typed. No `mixed`.
  Array parameters carry a PHPDoc shape.
- No optional parameters with a default that changes behaviour; make the two
  behaviours two methods.

## Naming

- Names say what a thing is or does, in full words: `$subscriptionRenewalDate`,
  not `$srd`, `$data`, `$tmp`, `$res`.
- Methods returning `bool` read as a question: `isActive()`, `hasCredit()`,
  `canRefund()`.
- Methods that cause an effect are verbs (`renew()`); methods that answer are
  nouns or questions (`totalDue()`, `isPaid()`). A method never does both.
- No abbreviations except established domain terms (`vat`, `sku`, `url`).

## What must not exist

- Commented-out code, unreachable branches, `TODO` stubs with no owner, unused
  variables, parameters, imports, methods, properties, classes, routes, or
  config keys. Delete them — version control is the archive.
- Narrative comments that restate the code. Rename the thing instead.
- The same logic in three places: the third occurrence gets extracted.
