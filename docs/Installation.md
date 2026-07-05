# Installation

## Requirements

- PHP **8.2+**
- Laravel **11, 12, or 13**
- [Claude Code](https://claude.com/claude-code)
- `pcov` or Xdebug (optional, to enforce the 80% coverage gate)

## From Packagist

```bash
composer require --dev mohamed-ashraf-elsaed/claude-kit
php artisan claude-kit:install
```

Then install the tooling the kit added and (if you scaffolded a frontend) the npm
dependencies:

```bash
composer install   # installs Pint/PHPStan/Pest/Boost + wires the git pre-commit hook
npm install        # only if a frontend stack was set up
```

## From GitHub (before Packagist, or for a fork)

Add a VCS repository and require the branch or a tag:

```json
{
    "repositories": [
        { "type": "vcs", "url": "https://github.com/mohamed-ashraf-elsaed/claude-kit" }
    ]
}
```

```bash
composer require --dev mohamed-ashraf-elsaed/claude-kit:dev-main
php artisan claude-kit:install
```

## What happens on install

1. The installer detects your **frontend stack** and asks you to confirm it.
2. It asks which **parts** to scaffold (all by default).
3. It writes the files, **skipping any that already exist** (use `--force` to
   overwrite) and **merging** `composer.json` / `package.json`.
4. It configures the git hooks path if the project is a git repo.

See **[Usage](Usage)** for flags and non-interactive installs.

## Verifying

```bash
vendor/mohamed-ashraf-elsaed/claude-kit/runtime/quality-checks.sh
```

Open Claude Code in the project, make a small code change, and confirm the Stop
hook runs the gate. Coverage enforcement needs a driver:

```bash
sudo apt install -y php8.4-pcov   # example for PHP 8.4 on Debian/Ubuntu
```
