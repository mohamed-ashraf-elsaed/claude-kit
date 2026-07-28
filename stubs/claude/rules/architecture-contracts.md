---
paths:
  - "app/**/*.php"
---

<!-- Published by claude-kit because this project selected Contracts.
     These are claude-kit's opinionated defaults, not a description of your
     codebase: read them, adapt anything that does not match how you actually
     work, and delete what you do not want. -->

# Contracts (`app/Contracts`)

Interfaces for the places where this application meets something it does not
control.

## When to add one

- Add a contract for a genuine boundary: a payment gateway, an SMS provider, a
  storage backend, an external API, a clock. Something with more than one real
  implementation, or one you must fake in tests.
- Do **not** add an interface for a class with a single implementation that will
  never be swapped. A `final` class is the default; an interface is a decision
  you justify.

## Shape

- Interfaces only in `app/Contracts` — no abstract classes, no traits, no
  constants carrying configuration.
- Named for the role, not the pattern: `PaymentGateway`, not
  `PaymentGatewayInterface` or `IPaymentGateway`.
- Keep them narrow. A consumer that needs one method should not have to
  implement six; split the interface instead of padding implementations with
  methods that throw.
- Every method is fully typed, including the return type. Return a value object
  or a data object rather than an untyped array.

## Wiring

- Bind the concrete implementation in a service provider
  (`$this->app->bind(PaymentGateway::class, StripeGateway::class)`).
- Consumers typehint the contract. The concrete class name appears only in the
  service provider and in its own tests.
- Test doubles implement the contract; do not mock the concrete class.
