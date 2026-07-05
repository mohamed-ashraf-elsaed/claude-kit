#!/usr/bin/env bash
#
# claude-kit shared quality gate. Runs the full code-quality suite against the
# CURRENT WORKING DIRECTORY (which the caller sets to the project root):
#
#   PHP:      Pint (style), PHPStan level 7 + strict-rules, Pest + coverage gate
#   Frontend: ESLint, Prettier, and a type check — but only if the host
#             package.json actually defines those scripts (so the same script is
#             correct for Vue, React, Blade, and API-only projects).
#
# Exits 0 if everything passes, 1 otherwise, with a combined report on stderr.
# This is the single source of truth invoked by the git pre-commit hook and by
# Claude Code's Stop hook, so the gate is identical everywhere. It lives in
# vendor/ and is referenced (not copied), so `composer update` propagates fixes.

set -uo pipefail

MIN_COVERAGE="${CLAUDE_KIT_MIN_COVERAGE:-80}"
FAILED=0
REASONS=""

fail() {
    FAILED=1
    REASONS+=$'\n=== '"$1"$' ===\n'"$2"$'\n'
}

has_npm_script() {
    [ -f package.json ] || return 1
    node -e "process.exit((require('./package.json').scripts||{})['$1']?0:1)" 2>/dev/null
}

# --- PHP ------------------------------------------------------------------
if [ -d app ] && [ -x vendor/bin/pint ]; then
    if ! OUT="$(vendor/bin/pint --test 2>&1)"; then
        fail "Pint code style" "$OUT"
    fi

    if [ -x vendor/bin/phpstan ]; then
        if ! OUT="$(vendor/bin/phpstan analyse --no-progress --error-format=raw 2>&1)"; then
            fail "PHPStan (level 7 + strict-rules)" "$OUT"
        fi
    fi

    if [ -x vendor/bin/pest ]; then
        if php -m 2>/dev/null | grep -qiE '^(pcov|xdebug)$'; then
            if ! OUT="$(vendor/bin/pest --coverage --min="$MIN_COVERAGE" --compact 2>&1)"; then
                fail "Pest / coverage < ${MIN_COVERAGE}%" "$OUT"
            fi
        else
            if ! OUT="$(vendor/bin/pest --compact 2>&1)"; then
                fail "Pest test suite" "$OUT"
            fi
            REASONS+=$'\n[WARNING] No coverage driver (pcov/Xdebug); '"${MIN_COVERAGE}"$'% gate not enforced.\n'
        fi
    fi
fi

# --- Frontend (only what the host actually configured) --------------------
if [ -f package.json ] && [ -d node_modules ]; then
    if has_npm_script lint:check && ! OUT="$(npm run --silent lint:check 2>&1)"; then
        fail "ESLint" "$OUT"
    fi

    if has_npm_script format:check && ! OUT="$(npm run --silent format:check 2>&1)"; then
        fail "Prettier formatting" "$OUT"
    fi

    if has_npm_script types:check && ! OUT="$(npm run --silent types:check 2>&1)"; then
        fail "Frontend type check" "$OUT"
    fi
fi

if [ "$FAILED" -eq 1 ]; then
    echo "$REASONS" >&2
    exit 1
fi

exit 0
