# Feature: <Feature Name>

> Copy this file to `features/<feature-name>/FEATURE.md` and fill in every
> section. Delete these quote blocks when done.

| | |
| --- | --- |
| **Status** | <planned / in progress / shipped> |
| **Owner** | <name> |
| **Created** | <YYYY-MM-DD> |
| **Last updated** | <YYYY-MM-DD> |

## 1. Summary

> One or two sentences: what this feature does and who it is for.

## 2. Business logic & rules

> The domain rules that govern this feature. Be explicit and unambiguous —
> this is the part that must never be guessed at later. Cover:
> - The core rules / invariants (what must always be true).
> - Validation rules and constraints.
> - Edge cases and how they are handled.
> - Authorization: who can do what (which role/owner).
> - Multi-tenant isolation (if applicable): how owner scoping is enforced.

## 3. User / request flow

> Step-by-step: what happens from the entry point to the response.
> e.g. 1. User submits the sign-in form → 2. `LoginRequest` validates → ...

## 4. What it calls (entry points & collaborators)

| Layer | Class / file | Responsibility |
| --- | --- | --- |
| Route | `routes/web.php` → `...` | <which route(s)> |
| Controller | `App\Http\Controllers\...` | <thin entry point> |
| Form Request | `App\Http\Requests\...` | <validation> |
| Service | `App\Services\...Service` | <business logic> |
| Action | `App\Actions\...Action` | <single-purpose unit> |
| Job / Queue | `App\Jobs\...` | <async work, if any> |
| Event / Listener | `App\Events\...` | <side effects, if any> |
| Model(s) | `App\Models\...` | <persistence> |

## 5. Responses & data objects

> What this feature returns. List the response classes / DTOs / pages / JSON
> resources and their shape.

| Returned by | Type | Shape / fields |
| --- | --- | --- |
| `...` | `App\Data\...Data` / page / JSON | <fields> |

## 6. Data model

> Tables and columns this feature owns or touches, key relationships, and the
> owner key used for tenant isolation (if applicable).

## 7. Dependencies

> External services, packages, env vars, or other features this relies on.

## 8. Tests

> Where the tests live and what they cover (`tests/Feature/...`,
> `tests/Unit/...`). Note the coverage expectation (>= 80%).

## 9. Change log

> Append an entry for every change or bug fix to this feature. Newest first.

- **<YYYY-MM-DD>** — <what changed and why>. Deploy impact: <none / see DEPLOY.md>.
