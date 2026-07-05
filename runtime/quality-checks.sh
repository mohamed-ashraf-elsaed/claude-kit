#!/usr/bin/env bash
#
# claude-kit shared quality gate. Runs against the CURRENT WORKING DIRECTORY
# (the caller sets it to the project root). What runs is driven by what the
# installer scaffolded:
#
#   Pint      — if pint.json exists
#   PHPStan   — if phpstan.neon exists (its level lives in that file)
#   Tests     — per .claude-kit.json (tool + coverage_min); falls back to Pest
#   Frontend  — ESLint/Prettier/type-check, only if package.json defines them
#
# Exits 0 on success, 1 otherwise, with a combined report on stderr. Backs the
# git pre-commit hook, Claude's Stop hook, and CI. Lives in vendor/ and is
# referenced (not copied), so `composer update` propagates fixes.

set -uo pipefail

DEFAULT_COVERAGE="${CLAUDE_KIT_MIN_COVERAGE:-80}"
FAILED=0
REASONS=""

fail() {
    FAILED=1
    REASONS+=$'\n=== '"$1"$' ===\n'"$2"$'\n'
}

# Read a dotted key from .claude-kit.json; prints the value (bool as true/false)
# or nothing (exit 1) when absent.
manifest() {
    php -r '$c=@json_decode(@file_get_contents(".claude-kit.json"),true); if(!is_array($c)){exit(1);} $v=$c; foreach(explode(".",$argv[1]) as $k){ if(!is_array($v)||!array_key_exists($k,$v)){exit(1);} $v=$v[$k]; } if(is_bool($v)){echo $v?"true":"false";}elseif($v===null){exit(1);}else{echo $v;}' "$1" 2>/dev/null
}

has_npm_script() {
    [ -f package.json ] || return 1
    node -e "process.exit((require('./package.json').scripts||{})['$1']?0:1)" 2>/dev/null
}

# --- PHP: Pint ------------------------------------------------------------
if [ -f pint.json ] && [ -x vendor/bin/pint ] && ! OUT="$(vendor/bin/pint --test 2>&1)"; then
    fail "Pint code style" "$OUT"
fi

# --- PHP: PHPStan ---------------------------------------------------------
if [ -f phpstan.neon ] && [ -x vendor/bin/phpstan ] && ! OUT="$(vendor/bin/phpstan analyse --no-progress --error-format=raw 2>&1)"; then
    fail "PHPStan static analysis" "$OUT"
fi

# --- PHP: Tests -----------------------------------------------------------
TESTS_ENABLED="$(manifest tests.enabled || true)"
TEST_TOOL="$(manifest tests.tool || true)"
COVERAGE="$(manifest tests.coverage_min || true)"

# Fallback for projects installed before the manifest existed.
if [ -z "$TESTS_ENABLED" ] && [ -x vendor/bin/pest ]; then
    TESTS_ENABLED="true"
    TEST_TOOL="pest"
    COVERAGE="$DEFAULT_COVERAGE"
fi

if [ "$TESTS_ENABLED" = "true" ]; then
    [ -n "$TEST_TOOL" ] || TEST_TOOL="pest"
    BIN="vendor/bin/$TEST_TOOL"

    if [ -x "$BIN" ]; then
        HAS_DRIVER=0
        php -m 2>/dev/null | grep -qiE '^(pcov|xdebug)$' && HAS_DRIVER=1

        if [ "$TEST_TOOL" = "pest" ] && [ -n "$COVERAGE" ] && [ "$HAS_DRIVER" = "1" ]; then
            OUT="$("$BIN" --coverage --min="$COVERAGE" --compact 2>&1)" || fail "Tests / coverage < ${COVERAGE}%" "$OUT"
        elif [ "$TEST_TOOL" = "pest" ]; then
            OUT="$("$BIN" --compact 2>&1)" || fail "Test suite" "$OUT"
            [ -n "$COVERAGE" ] && [ "$HAS_DRIVER" = "0" ] && REASONS+=$'\n[WARNING] No coverage driver (pcov/Xdebug); coverage gate not enforced.\n'
        else
            OUT="$("$BIN" 2>&1)" || fail "Test suite" "$OUT"
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
