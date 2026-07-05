# Releasing

`claude-kit` follows [Semantic Versioning](https://semver.org/spec/v2.0.0.html)
(`MAJOR.MINOR.PATCH`) and tags releases as `vX.Y.Z`.

- **PATCH** (`v0.1.0` → `v0.1.1`) — bug fixes and internal changes that do not
  alter the installed output for existing stacks.
- **MINOR** (`v0.1.0` → `v0.2.0`) — new features, new stacks, new stubs, or
  additive changes that are backwards compatible.
- **MAJOR** (`v0.9.0` → `v1.0.0`) — breaking changes to the installed output,
  the command signature, or the runtime contract referenced from `vendor/`.

> While the package is `0.x`, MINOR may include breaking changes, as allowed by
> SemVer for initial development.

## The rule for every change

Every user-facing change — feature or fix — **must add an entry under
`## [Unreleased]` in [CHANGELOG.md](CHANGELOG.md)** in the same pull request,
using the Keep a Changelog categories (`Added`, `Changed`, `Fixed`, `Removed`,
`Deprecated`, `Security`). This is enforced by the repo's Claude Stop hook and
by the pre-commit gate, so unreleased notes never fall behind the code.

Tags are cut by a maintainer (or by Claude when a change warrants a release, per
the repo `CLAUDE.md`) — not on every commit.

## Cutting a release

1. Ensure `main` is green (`composer check`) and the `## [Unreleased]` section
   lists everything shipping.
2. Pick the new version `X.Y.Z` per the rules above.
3. In `CHANGELOG.md`, rename `## [Unreleased]` to `## [X.Y.Z] - YYYY-MM-DD`, add
   a fresh empty `## [Unreleased]` above it, and update the compare/tag links at
   the bottom.
4. Commit: `git commit -am "Release vX.Y.Z"`.
5. Tag and push:

   ```bash
   git tag -a vX.Y.Z -m "vX.Y.Z"
   git push origin main --follow-tags
   ```

6. Pushing the tag triggers `.github/workflows/release.yml`, which creates the
   GitHub Release with notes extracted from the changelog.
7. [Packagist](https://packagist.org) updates automatically via the GitHub
   webhook (see [docs/Publishing.md](docs/Publishing.md)); no manual step.

## Rolling back a bad release

```bash
git tag -d vX.Y.Z                 # delete local tag
git push origin :refs/tags/vX.Y.Z # delete remote tag
```

Then delete the GitHub Release from the UI and cut a fixed `vX.Y.(Z+1)`.
Never re-point an existing tag — publish a new one.
