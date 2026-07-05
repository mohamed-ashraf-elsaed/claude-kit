#!/usr/bin/env bash
#
# The claude-kit repository's own quality gate (the package dogfoods its own
# philosophy). Runs Pint, PHPStan, and Pest, then enforces the changelog rule:
# any change under src/, runtime/, or stubs/ must be accompanied by a
# CHANGELOG.md update (see RELEASING.md). Invoked by .githooks/pre-commit and by
# Claude's Stop hook. Exits 0 on success, 1 otherwise, report on stderr.

set -uo pipefail

cd "$(dirname "$0")/.." || exit 1

FAILED=0
REASONS=""

fail() {
    FAILED=1
    REASONS+=$'\n=== '"$1"$' ===\n'"$2"$'\n'
}

if [ -x vendor/bin/pint ] && ! OUT="$(vendor/bin/pint --test 2>&1)"; then
    fail "Pint code style" "$OUT"
fi

if [ -x vendor/bin/phpstan ] && ! OUT="$(vendor/bin/phpstan analyse --no-progress --error-format=raw 2>&1)"; then
    fail "PHPStan (level 7 + strict-rules)" "$OUT"
fi

if [ -x vendor/bin/pest ] && ! OUT="$(vendor/bin/pest --compact 2>&1)"; then
    fail "Pest test suite" "$OUT"
fi

# --- Changelog gate -------------------------------------------------------
if git rev-parse --verify --quiet HEAD >/dev/null 2>&1; then
    CHANGED="$( { git diff HEAD --name-only; git ls-files --others --exclude-standard; } 2>/dev/null | sort -u )"
    CODE_CHANGED="$(printf '%s\n' "$CHANGED" | grep -E '^(src|runtime|stubs)/' || true)"
    CHANGELOG_CHANGED="$(printf '%s\n' "$CHANGED" | grep -E '^CHANGELOG\.md$' || true)"

    if [ -n "$CODE_CHANGED" ] && [ -z "$CHANGELOG_CHANGED" ]; then
        fail "Missing changelog entry" "src/, runtime/, or stubs/ changed but CHANGELOG.md was not updated. Add an entry under ## [Unreleased] (see RELEASING.md)."
    fi
fi

if [ "$FAILED" -eq 1 ]; then
    echo "$REASONS" >&2
    exit 1
fi

exit 0
