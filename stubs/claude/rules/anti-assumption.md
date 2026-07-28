<!-- Published by claude-kit. Always loaded — no `paths` frontmatter, because it
     applies to every change in every directory. Every convention below is a
     DRAFT: adjust it to your team's real standard, then delete the DRAFT
     markers. -->

# Verify, or ask. Never invent.

<!-- DRAFT -->
## The rule

Every fact you act on comes from this repository, its database schema, or the
official documentation of the package involved. If you cannot find it, ask. A
guess presented as a fact is worse than a question.

<!-- DRAFT -->
## Check, do not remember

- **Column and table names** — read the migration or the model's casts, not the
  variable name that suggests them.
- **Method and route names** — open the class or `routes/`. A plausible name from
  another project is still a guess.
- **Package APIs and versions** — check the installed version in
  `composer.json` / `package.json`, then that version's documentation. Package
  APIs change between majors.
- **Config and environment keys** — read `config/` and `.env.example`. Never
  invent a key name, and never assume a value is set.
- **Existing conventions** — before adding a pattern, look at how the two
  nearest sibling files do it and follow that.

<!-- DRAFT -->
## Stop and ask when

- Two readings of the request would produce materially different work.
- The change is hard to reverse: a data model change, a destructive migration, a
  new dependency, a public API contract, anything touching money, auth, or
  tenancy.
- The information you need is not in the repo and not in the docs.

<!-- DRAFT -->
## When you must proceed

State the assumption in one line before the work, not after it: "Assuming
`orders.status` is the enum in `OrderStatus` — say so if not." Then keep going.
Never let an unstated assumption reach the diff.

<!-- DRAFT -->
## Reporting

Report what actually happened. If tests fail, show the failure. If a step was
skipped, say which and why. "Done" means verified — the gate ran and passed.
