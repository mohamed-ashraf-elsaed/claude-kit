<!-- Published by claude-kit. Always loaded — no `paths` frontmatter, because it
     applies to every change in every directory.
     This is claude-kit's default convention; adapt it to your team's. -->

# Build the smallest thing that satisfies the requirement

## The rule

Implement what was asked, at the size it was asked for. A requirement that has
not been stated does not exist yet. If you believe something extra is genuinely
required, say so and ask — do not build it and mention it afterwards.

## Do not add these unless a stated requirement needs them

- An interface, abstract class, or trait with exactly one implementation or one
  use site.
- A layer whose name is its job description: `Manager`, `Handler`, `Processor`,
  `Factory`, `Helper`, `Util`, wrapping a single call.
- A config key, environment variable, or feature flag nobody asked to configure.
- An event, listener, or observer where a direct call would do.
- Caching, queueing, batching, or a retry policy added for a load nobody has
  measured.
- A generic solution for one concrete case: parameters, hooks, or `$options`
  arrays "for later".
- A new dependency where the framework or PHP standard library already covers it.

## Prefer

- A `final` class over an interface plus an implementation.
- A concrete method with two arguments over a configurable pipeline.
- Duplication twice over an abstraction that fits neither case. Extract on the
  third occurrence, when the shape is actually known.
- Deleting code over keeping it behind a flag.

## When you notice extra scope

Stop and say which part is beyond the request, what it would cost, and what you
recommend. Then implement the requested scope. Adding it silently is the failure
mode this rule exists to prevent.
