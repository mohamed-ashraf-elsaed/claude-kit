---
paths:
  - "**/*.php"
---

<!-- Published by claude-kit: the BALANCED variant of the clean-code rule. The
     other variants are `strict` (hard limits) and `relaxed` (direction without
     thresholds). Every convention below is a DRAFT: adjust the numbers to your
     team's real standard, then delete the DRAFT markers. -->

# Clean code — balanced

These limits are the default. Crossing one is allowed when the alternative is
genuinely worse — say so in the pull request, in one line, and move on.

| Limit | Balanced |
| --- | --- |
| Method body | 40 lines |
| Class body | 300 lines |
| Parameters | 5 |
| Nesting depth | 3 |
| `else` | allowed for a genuine two-way branch |

<!-- DRAFT -->
## Size

- Aim for method bodies under **40 lines**. Past that, look for a private method
  to extract; keep it whole only when splitting would separate steps that are
  read together.
- Classes stay under **300 lines**. A larger one is a signal to re-read its
  responsibilities, not an automatic failure.
- One class, interface, enum, or trait per file.

<!-- DRAFT -->
## Control flow

- Handle the exceptional cases first with guard clauses and return early; keep
  the happy path at the outermost level of the method.
- `else` is fine for one genuine either/or. An `else if` chain over three
  branches becomes a `match`, a lookup table, or polymorphism.
- Keep nesting to **3 levels**. Deeper usually means the inner block wants to be
  its own method.
- `match` over `switch` for value mapping.

<!-- DRAFT -->
## Signatures

- Up to **5 parameters**. Beyond that, group the related ones into one typed
  object.
- Avoid boolean flags that select behaviour; two named methods are clearer. A
  boolean that is genuinely a property of the input (`$includeArchived`) is fine.
- Every parameter, property, and return value is explicitly typed. `array`
  carries a PHPDoc shape when the shape matters.

<!-- DRAFT -->
## Naming

- Full words over abbreviations: `$invoiceTotal`, not `$invTot`. `$data`,
  `$info`, `$temp`, and `$result` are placeholders, not names.
- `bool` returns read as a question: `isActive()`, `hasCredit()`, `canRefund()`.
- Commands are verbs (`archive()`); queries are nouns or questions
  (`archivedAt()`, `isArchived()`). Prefer not to mix the two in one method.

<!-- DRAFT -->
## What must not exist

- Commented-out code, unreachable branches, unused variables, parameters,
  imports, methods, properties, classes, routes, or config keys. Delete them.
- Comments that restate the code. Keep comments for the *why*: a non-obvious
  constraint, a workaround with a link, an array shape, a `@throws`.
- Copy-pasted logic in a third place; the third occurrence gets extracted.
