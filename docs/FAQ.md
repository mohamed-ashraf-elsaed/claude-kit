# ❓ FAQ

<details open>
<summary><strong>Is this a Composer or an npm package?</strong></summary>

Composer. The audience is Laravel developers, the bulk of the tooling is PHP (Pint, PHPStan, Pest, Boost) that only Composer installs, and the idiomatic entry point is an Artisan command. Frontend config files are copied and their npm devDependencies are merged into your `package.json` — npm is never the installer.
</details>

<details>
<summary><strong>Will it overwrite my existing files?</strong></summary>

No. Existing files are skipped unless you pass `--force`. `composer.json` and `package.json` are merged, preserving your entries. Re-running is idempotent.
</details>

<details>
<summary><strong>I have no frontend / it's an API. Will it still work?</strong></summary>

Yes. Choose (or let it detect) the `none` stack: no ESLint/Prettier/TS files are written and the gate runs the PHP checks only.
</details>

<details>
<summary><strong>Does it lock me into these exact rules?</strong></summary>

No. `CLAUDE.md`, the skills, `pint.json`, `phpstan.neon`, and the linter configs are copied into your repo — edit them freely. Only the referenced machinery in `vendor/…/runtime/` is shared, and you can override it locally.
</details>

<details>
<summary><strong>How do updates reach my project?</strong></summary>

Machinery (`quality-checks.sh`, the Stop hook) auto-updates via `composer update`. Copied content refreshes when you re-run `claude-kit:install --force`. See <a href="Upgrading">Upgrading</a>.
</details>

<details>
<summary><strong>The coverage gate isn't failing on low coverage.</strong></summary>

You need a coverage driver (`pcov` or Xdebug). Without one, the suite still runs but coverage is only warned, not enforced. Install `pcov` and it activates.
</details>

<details>
<summary><strong>Can I disable the "feature docs required" gate?</strong></summary>

Yes — set `CLAUDE_KIT_FEATURE_DOCS=0`, or `hooks.feature_docs: false` in `.claude-kit.json`.
</details>

<details>
<summary><strong>Which Laravel and PHP versions are supported?</strong></summary>

Laravel 11, 12, and 13; PHP 8.2–8.4. CI runs the matrix on every push.
</details>

<details>
<summary><strong>How do I report a bug or a security issue?</strong></summary>

Bugs: open an issue with the template. Security: report privately — see <a href="https://github.com/mohamed-ashraf-elsaed/claude-kit/blob/main/SECURITY.md">SECURITY.md</a>.
</details>

---

Still stuck? [Open a discussion](https://github.com/mohamed-ashraf-elsaed/claude-kit/discussions) or an [issue](https://github.com/mohamed-ashraf-elsaed/claude-kit/issues).

---
<sub>[← Upgrading](Upgrading) · 🏠 [Home](Home)</sub>
