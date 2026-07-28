---
paths:
  - "app/**/*.php"
---

<!-- Published by claude-kit because this project selected the Actions layer.
     Sections marked DRAFT are a starting point: check them against how this
     codebase actually works, correct them, then delete the DRAFT markers. The
     unmarked section below is a settled decision, not a draft. -->

# Actions (`app/Actions`)

One class, one operation. An action is the unit of work a controller, a command,
or a job can invoke without knowing how it is done.

## The public method

Actions expose exactly one public method: `__invoke()`. This overrides any
example elsewhere (including bundled skills) showing `execute()` or other method
names.

Everything else in the class is private. The architecture test suite enforces
this, so a second public method fails the gate.

<!-- DRAFT -->
## Shape

- `final class`, suffixed `Action` (`CancelSubscriptionAction`), in
  `app/Actions`.
- `__invoke()` returns the result the caller needs — the created model, the
  computed value — rather than `void` plus a follow-up query.

<!-- DRAFT -->
## Scope

- One business operation per action, named as a verb phrase. If the name needs
  "and" ('CreateAndNotify'), it is two actions.
- Arguments are typed values or models, never a raw `array` of request input.
- Collaborators arrive through the constructor; the container resolves them.

<!-- DRAFT -->
## Composition

- An action may call other actions and services. Nothing calls an action from
  inside a model.
- Idempotency is the action's job when it can be invoked from a retryable
  context (a queued job, a webhook): check for the already-done state first and
  return the existing result.
- Anything that must not run twice on a retry wraps its writes in
  `DB::transaction()`.
