# claude-kit

[![Latest version](https://img.shields.io/packagist/v/mohamed-ashraf-elsaed/claude-kit.svg?style=flat-square)](https://packagist.org/packages/mohamed-ashraf-elsaed/claude-kit)
[![Tests](https://img.shields.io/github/actions/workflow/status/mohamed-ashraf-elsaed/claude-kit/tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/mohamed-ashraf-elsaed/claude-kit/actions/workflows/tests.yml)
[![Lint](https://img.shields.io/github/actions/workflow/status/mohamed-ashraf-elsaed/claude-kit/lint.yml?branch=main&label=lint&style=flat-square)](https://github.com/mohamed-ashraf-elsaed/claude-kit/actions/workflows/lint.yml)
[![PHP version](https://img.shields.io/packagist/php-v/mohamed-ashraf-elsaed/claude-kit?style=flat-square)](https://packagist.org/packages/mohamed-ashraf-elsaed/claude-kit)
[![Downloads](https://img.shields.io/packagist/dt/mohamed-ashraf-elsaed/claude-kit.svg?style=flat-square)](https://packagist.org/packages/mohamed-ashraf-elsaed/claude-kit)
[![License](https://img.shields.io/packagist/l/mohamed-ashraf-elsaed/claude-kit.svg?style=flat-square)](LICENSE)

**One command sets up a Laravel project for [Claude Code](https://claude.com/claude-code):**
the engineering rules, a Stop-hook + pre-commit **quality gate**, reusable
skills, PHPStan level 7 + strict-rules, Pint, Pest with an 80% coverage gate, an
architecture test suite, feature-doc scaffolding, and **stack-aware frontend
tooling** (Vue, React, Blade, or none).

It packages a battle-tested setup so every project — and every developer — gets
the same guardrails instead of hand-copying config between repos.

## Table of contents

- [Requirements](#requirements)
- [Installation](#installation)
- [What it installs](#what-it-installs)
- [Choosing what to install](#choosing-what-to-install)
- [The hybrid update model](#the-hybrid-update-model)
- [The quality gate](#the-quality-gate)
- [Documentation](#documentation)
- [Contributing](#contributing)
- [Security](#security)
- [License](#license)

## Requirements

- PHP **8.2+**
- Laravel **11, 12, or 13**
- [Claude Code](https://claude.com/claude-code) (to use the rules, hooks, and skills)
- A coverage driver (`pcov` or Xdebug) to enforce the 80% coverage gate

## Installation

```bash
composer require --dev mohamed-ashraf-elsaed/claude-kit
php artisan claude-kit:install
```

The installer detects your frontend stack (Inertia + Vue, Inertia + React,
Blade/Livewire, or API-only), asks which parts to scaffold, and writes them into
your project. Then:

```bash
composer install   # installs the dev tooling and wires the git pre-commit hook
npm install        # only if a frontend stack was set up
```

Not published to Packagist yet? Install straight from GitHub by adding this to
your `composer.json` and running `composer require --dev mohamed-ashraf-elsaed/claude-kit:dev-main`:

```json
{
    "repositories": [
        { "type": "vcs", "url": "https://github.com/mohamed-ashraf-elsaed/claude-kit" }
    ]
}
```

## What it installs

| Part | What lands in your project |
| --- | --- |
| **.claude core** | `.claude/settings.json` (Stop-hook wiring + permission allowlist), the reusable skills for your stack, and `.mcp.json` (Laravel Boost). |
| **CLAUDE.md** | Generic engineering rules (architecture layers, hard typing, hygiene, testing) with `TODO` placeholders for your product/integrations/deployment. |
| **Quality gate** | `phpstan.neon` (level 7 + strict-rules via the vendored base), `pint.json`, `tests/Arch/ArchTest.php`, `.githooks/pre-commit`, and merged composer scripts. |
| **Frontend** | Stack-matching `eslint.config.js` / `.prettierrc` / `tsconfig.json`, plus merged `package.json` scripts and devDependencies. Skipped for API-only projects. |
| **Docs** | `features/_TEMPLATE/` + `features/README.md`, `.editorconfig`, `.gitattributes`. |
| **CI** | Generic GitHub Actions `tests.yml` and `lint.yml`. |

Existing files are never clobbered — the installer skips them unless you pass
`--force`. `composer.json` and `package.json` are merged, preserving your entries.

## Choosing what to install

```bash
# Auto-detect the stack, choose parts interactively (default)
php artisan claude-kit:install

# Non-interactive: pick the stack and a subset of parts
php artisan claude-kit:install --stack=inertia-react --parts=claude,quality,frontend

# Re-run and overwrite (e.g. to refresh skills after an update)
php artisan claude-kit:install --force
```

- **Stacks:** `inertia-vue`, `inertia-react`, `blade`, `none`
- **Parts:** `claude`, `rules`, `quality`, `frontend`, `docs`, `ci`

## The hybrid update model

- **Machinery** — `quality-checks.sh`, the Stop hook, and the PHPStan base
  config — lives in `vendor/mohamed-ashraf-elsaed/claude-kit/runtime/` and is
  *referenced*, so `composer update` propagates fixes to every project automatically.
- **Content you own** — `CLAUDE.md`, the skills, the feature templates, and the
  linter configs — is *copied* into your repo so you can customise it freely.

## The quality gate

`vendor/mohamed-ashraf-elsaed/claude-kit/runtime/quality-checks.sh` is the single
source of truth. It runs Pint, PHPStan (level 7 + strict-rules), and Pest
(+80% coverage), then the frontend checks your `package.json` actually defines.
The same script backs the git pre-commit hook, Claude's Stop hook, and CI — so
the gate is identical everywhere.

- Coverage needs the `pcov` or Xdebug extension; without it, the gate warns
  instead of blocking on coverage only.
- Disable the feature-doc requirement with `CLAUDE_KIT_FEATURE_DOCS=0`.

## Documentation

Full guides live in the [wiki](https://github.com/mohamed-ashraf-elsaed/claude-kit/wiki)
and the [`docs/`](docs/) directory:

- [Installation](docs/Installation.md)
- [Usage](docs/Usage.md)
- [Configuration](docs/Configuration.md)
- [Frontend stacks](docs/Frontend-Stacks.md)
- [Quality gate](docs/Quality-Gate.md)
- [Skills](docs/Skills.md)
- [Architecture](docs/Architecture.md)
- [Publishing to Packagist](docs/Publishing.md)
- [Upgrading](docs/Upgrading.md)
- [FAQ](docs/FAQ.md)

## Contributing

Contributions are welcome! Please read [CONTRIBUTING.md](CONTRIBUTING.md) and the
[Code of Conduct](CODE_OF_CONDUCT.md). Releases follow [SemVer](https://semver.org);
see [RELEASING.md](RELEASING.md).

## Security

Please report vulnerabilities privately — see [SECURITY.md](SECURITY.md).

## License

The MIT License. See [LICENSE](LICENSE).
