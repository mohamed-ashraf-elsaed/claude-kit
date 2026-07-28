---
paths:
  - "**/*.php"
---

<!-- Published by claude-kit: the RELAXED variant of the clean-code rule. The
     other variants are `strict` (hard limits) and `balanced` (limits with escape
     hatches). This variant sets no thresholds on purpose — adjust the guidance
     to your team's real standard. -->

# Clean code — relaxed

No line or parameter counts. What matters is that the next person can read the
method top to bottom and predict what it does.

| Limit | Relaxed |
| --- | --- |
| Method body | no limit — split when it stops reading as one idea |
| Class body | no limit — split when it has two reasons to change |
| Parameters | no limit — group them when the list stops being obvious |
| Nesting depth | no limit — flatten when you lose track of the condition |
| `else` | allowed |

## Direction

- A method should do one thing at one level of abstraction. When you find
  yourself scrolling to hold it in your head, extract the part you had to
  re-read.
- Split a class when it has two independent reasons to change, not when it
  reaches a line count.
- When a parameter list stops being self-explanatory at the call site, pass an
  object or use named arguments.

## Readability first

- Return early for error and edge cases so the main path stays at the top level.
  Deep nesting is the thing to avoid, not `else` itself.
- Prefer `match` to a long `if`/`elseif` chain that maps a value to a value.
- Name things in full words. `$data`, `$info`, and `$temp` are the ones worth
  renaming; a short `$i` in a tight loop is not a problem.
- `bool` accessors read as questions (`isActive()`, `hasCredit()`).

## Still not negotiable

- No commented-out code and no unreachable branches — delete them, git remembers.
- No unused imports, variables, or parameters.
- Comments explain *why*, not *what*. If a comment explains what the code does,
  the code needs a better name, not the comment.
- Duplication is acceptable twice. The third copy gets extracted.
