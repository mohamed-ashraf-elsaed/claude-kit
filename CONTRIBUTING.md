# Contributing to claude-kit

Thanks for taking the time to contribute! This document explains how to get set
up, the quality bar every change must meet, and how releases work.

By participating you agree to abide by our [Code of Conduct](CODE_OF_CONDUCT.md).

## Ways to contribute

- **Report a bug** — open a [bug report](https://github.com/mohamed-ashraf-elsaed/claude-kit/issues/new?template=bug_report.yml).
- **Request a feature** — open a [feature request](https://github.com/mohamed-ashraf-elsaed/claude-kit/issues/new?template=feature_request.yml).
- **Send a pull request** — see below. For anything non-trivial, please open an
  issue first so we can agree on the approach.

## Local setup

Requires PHP 8.2+ and Composer.

```bash
git clone https://github.com/mohamed-ashraf-elsaed/claude-kit.git
cd claude-kit
composer install
```

Optional but recommended — enable the pre-commit gate so you catch problems
before pushing:

```bash
git config core.hooksPath .githooks
```

## Project layout

```
src/        PHP: the ServiceProvider, the install command, and Support/ classes
runtime/    Files REFERENCED from vendor/ in host projects (auto-update)
stubs/      Files PUBLISHED (copied) into host projects
tests/      Pest tests (Unit + Feature via Testbench)
docs/       Wiki / documentation pages
```

The hybrid split is deliberate — read
[docs/Architecture.md](docs/Architecture.md) before changing where a file lives.

## The quality bar (enforced)

Every change must pass the full gate. Run it locally:

```bash
composer check          # Pint (style) + PHPStan (level 7 + strict) + Pest
```

Or individually:

```bash
composer lint           # auto-fix code style with Pint
composer lint:check     # verify style without fixing
composer types:check    # PHPStan level 7 + strict-rules — must be zero errors
composer test           # Pest suite
composer test:coverage  # Pest with the 80% coverage gate (needs pcov/Xdebug)
```

CI runs the same checks across every supported PHP and Laravel version, and the
pre-commit hook runs them before each commit.

## Coding standards

- `declare(strict_types=1);` in every PHP file; explicit types everywhere.
- `final` classes; small, single-responsibility units; no dead or speculative code.
- Match the style of the surrounding code. Pint's Laravel preset is the arbiter.
- New behavior ships with tests. Prefer feature tests that exercise the installer
  against a temp directory; keep them deterministic (no network, no global state).

## Pull request checklist

1. Branch from `main`.
2. Make the change with tests.
3. `composer check` is green.
4. **Add a `## [Unreleased]` entry to [CHANGELOG.md](CHANGELOG.md)** describing
   what changed (see [Releasing](#releasing) — this is required for every
   user-facing change).
5. Update the relevant docs under `docs/` and the `README.md` if behavior changed.
6. Open the PR using the template; link the issue it closes.

Maintainers squash-merge; write a clear PR title (it becomes the commit).

## Releasing

This project follows [Semantic Versioning](https://semver.org). The full,
step-by-step release process — how the changelog, tags, GitHub Releases, and
Packagist fit together — lives in [RELEASING.md](RELEASING.md).

## License

By contributing, you agree that your contributions are licensed under the
project's [MIT License](LICENSE).
