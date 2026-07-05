# Publishing to Packagist

`claude-kit` is a normal Composer package. Publishing it makes
`composer require --dev mohamed-ashraf-elsaed/claude-kit` work for everyone.

## One-time setup

1. Push the repo to GitHub (public).
2. Go to <https://packagist.org>, sign in with GitHub, and choose **Submit**.
3. Paste the repo URL: `https://github.com/mohamed-ashraf-elsaed/claude-kit`.
   Packagist reads `composer.json` and registers the package as
   `mohamed-ashraf-elsaed/claude-kit`.
4. **Enable auto-updates** so new tags publish automatically. Either:
   - Install the [Packagist GitHub app](https://github.com/apps/packagist) on the
     repo (recommended), or
   - Add the Packagist webhook manually (Packagist shows the URL + your API token
     under your profile → *Show API Token*).

## Cutting a version

Follow [RELEASING.md](https://github.com/mohamed-ashraf-elsaed/claude-kit/blob/main/RELEASING.md):
update the changelog, tag `vX.Y.Z`, and push the tag. Packagist picks up the new
version within seconds via the webhook, and the `release.yml` workflow creates
the GitHub Release.

## Versions on Packagist

- Tagged commits (`vX.Y.Z`) become stable releases.
- The default branch is available as `dev-main` for early adopters.
- Consumers pin with normal constraints, e.g. `"mohamed-ashraf-elsaed/claude-kit": "^0.1"`.

## Before the first publish — checklist

- [ ] `composer validate --strict` passes
- [ ] `composer check` is green (Pint, PHPStan, Pest)
- [ ] `CHANGELOG.md` has a dated `[0.1.0]` section
- [ ] `v0.1.0` tag pushed
- [ ] Repo is public and the Packagist app/webhook is connected
