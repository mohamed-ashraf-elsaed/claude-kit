---
paths:
  - "app/**/*.php"
---

<!-- Published by claude-kit because this project selected the Services layer.
     Every convention below is a DRAFT: check it against how this codebase
     actually works, correct it, then delete the DRAFT markers. -->

# Services (`app/Services`)

Business logic and orchestration live here. A controller accepts input and hands
off; a service decides what happens.

<!-- DRAFT -->
## Shape

- One class per cohesive area of behaviour, `final`, suffixed `Service`
  (`SubscriptionService`), in `app/Services`.
- Public methods are named for the use case they perform — `cancel()`,
  `renewFor()` — not for the data they touch (`handleSubscription()`).
- A service that has grown past ~7 public methods is two services; split it by
  use case, not by entity.

<!-- DRAFT -->
## Dependencies

- Collaborators arrive through the constructor. Never call `app()`, `resolve()`,
  or `App::make()` inside a service.
- Services may depend on other services, but the graph stays acyclic. If two
  services need each other, the shared behaviour belongs in a third one.

<!-- DRAFT -->
## Boundaries

- No HTTP: a service never touches `request()`, `session()`, `redirect()`,
  `abort()`, or a response object. It receives resolved arguments and returns
  values or throws.
- No presentation: no view rendering, no formatting for display.
- Failures raise a domain exception (`app/Exceptions`), never a `false` or `null`
  that the caller has to interpret.

<!-- DRAFT -->
## State and transactions

- Stateless: no request- or user-scoped data in properties, because the
  container may reuse the instance.
- A service method that writes more than one table wraps its writes in
  `DB::transaction()`. Nested transactions are the caller's business, never the
  callee's.
- Queue dispatches and events fire *after* the transaction commits
  (`DB::afterCommit()` or `afterCommit` on the job), so a rollback cannot leak a
  side effect.
