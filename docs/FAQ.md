# FAQ

### Is this a Composer or an npm package?

Composer. The audience is Laravel developers, the bulk of the tooling is PHP
(Pint, PHPStan, Pest, Boost) that only Composer installs, and the idiomatic entry
point is an Artisan command. Frontend config files are copied and their npm
devDependencies are merged into your `package.json` — npm is never the installer.

### Will it overwrite my existing files?

No. Existing files are skipped unless you pass `--force`. `composer.json` and
`package.json` are merged, preserving your entries. Re-running is idempotent.

### I have no frontend / it's an API. Will it still work?

Yes. Choose (or let it detect) the `none` stack: no ESLint/Prettier/TS files are
written and the gate runs the PHP checks only.

### Does it lock me into these exact rules?

No. `CLAUDE.md`, the skills, `pint.json`, `phpstan.neon`, and the linter configs
are copied into your repo — edit them freely. Only the referenced machinery in
`vendor/…/runtime/` is shared, and you can override it in your local config.

### How do updates reach my project?

Machinery (`quality-checks.sh`, the Stop hook, the PHPStan base) auto-updates via
`composer update`. Copied content refreshes when you re-run
`claude-kit:install --force`. See [Upgrading](Upgrading).

### The coverage gate isn't failing on low coverage.

You need a coverage driver (`pcov` or Xdebug). Without one, the suite still runs
but coverage is only warned, not enforced. Install `pcov` and it activates.

### Can I disable the "feature docs required" gate?

Yes — set `CLAUDE_KIT_FEATURE_DOCS=0`.

### Which Laravel and PHP versions are supported?

Laravel 11, 12, and 13; PHP 8.2–8.4. CI runs the matrix on every push.

### How do I report a bug or a security issue?

Bugs: open an issue with the template. Security: report privately — see
[SECURITY.md](https://github.com/mohamed-ashraf-elsaed/claude-kit/blob/main/SECURITY.md).
