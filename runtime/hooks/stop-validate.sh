#!/usr/bin/env bash
#
# claude-kit Stop hook: enforce the quality gate when Claude finishes a turn.
#
#   1. The shared quality suite (Pint / PHPStan / Tests / Frontend) via the
#      sibling quality-checks.sh, restricted to the checks recorded in the
#      manifest (hooks.checks) and repeated until it has passed
#      hooks.min_passes times in a row. Any failure stops the loop, blocks the
#      turn, and feeds the report back to Claude.
#   2. Feature docs: any change under app/, database/, routes/, or resources/js/
#      must be accompanied by an added/updated doc under features/<name>/.
#      Controlled by CLAUDE_KIT_FEATURE_DOCS (env) or the manifest
#      (hooks.feature_docs); defaults on.
#
# The manifest is .claude/claude-kit.json, falling back to the legacy root
# .claude-kit.json. Exit code 2 blocks the stop and feeds stderr back to Claude.
# Lives in vendor/ and is referenced, so `composer update` propagates fixes.

set -uo pipefail

PROJECT_DIR="${CLAUDE_PROJECT_DIR:-$(git rev-parse --show-toplevel 2>/dev/null || pwd)}"
cd "$PROJECT_DIR" || exit 0

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
QUALITY="$SCRIPT_DIR/../quality-checks.sh"

FAILED=0
REASONS=""

# Read a dotted key from the manifest; prints the value (bool as true/false,
# list as a comma-separated string) or nothing (exit 1) when absent.
manifest() {
    php -r '
        $c = null;
        foreach ([".claude/claude-kit.json", ".claude-kit.json"] as $p) {
            if (is_file($p)) { $d = @json_decode(@file_get_contents($p), true); if (is_array($d)) { $c = $d; break; } }
        }
        if ($c === null) { exit(1); }
        $v = $c;
        foreach (explode(".", $argv[1]) as $k) { if (!is_array($v) || !array_key_exists($k, $v)) { exit(1); } $v = $v[$k]; }
        if (is_bool($v)) { echo $v ? "true" : "false"; }
        elseif (is_array($v)) { echo implode(",", $v); }
        elseif ($v === null) { exit(1); }
        else { echo $v; }
    ' "$1" 2>/dev/null
}

# --- 1. Shared quality suite ----------------------------------------------
CHECKS="$(manifest hooks.checks || true)"

if [ -n "$CHECKS" ]; then
    export CLAUDE_KIT_CHECKS="$CHECKS"
fi

# How many consecutive clean runs are required. Older installs have no value
# recorded, which means the historical behaviour: one run.
PASSES="$(manifest hooks.min_passes || true)"
case "$PASSES" in
    ''|*[!0-9]*) PASSES=1 ;;
esac
[ "$PASSES" -ge 1 ] || PASSES=1
[ "$PASSES" -le 5 ] || PASSES=5

if [ -f "$QUALITY" ]; then
    ATTEMPT=1
    while [ "$ATTEMPT" -le "$PASSES" ]; do
        if ! OUT="$(bash "$QUALITY" 2>&1)"; then
            FAILED=1
            REASONS+=$'\n=== Quality checks failed (run '"$ATTEMPT"' of '"$PASSES"$') ===\n'"$OUT"$'\n'
            break
        fi
        ATTEMPT=$((ATTEMPT + 1))
    done
fi

# --- 2. Feature documentation gate ----------------------------------------
FEATURE_DOCS="${CLAUDE_KIT_FEATURE_DOCS:-}"
if [ -z "$FEATURE_DOCS" ]; then
    if [ "$(manifest hooks.feature_docs || true)" = "false" ]; then
        FEATURE_DOCS=0
    else
        FEATURE_DOCS=1
    fi
fi

if [ "$FEATURE_DOCS" != "0" ] && git rev-parse --verify --quiet HEAD >/dev/null 2>&1; then
    CHANGED="$( { git diff HEAD --name-only; git ls-files --others --exclude-standard; } 2>/dev/null | sort -u )"

    CODE_CHANGED="$(printf '%s\n' "$CHANGED" | grep -E '^(app|database|routes|resources/js)/' || true)"
    FEATURE_CHANGED="$(printf '%s\n' "$CHANGED" \
        | grep -E '^features/' \
        | grep -vE '^features/(_TEMPLATE/|README\.md)' || true)"

    if [ -n "$CODE_CHANGED" ] && [ -z "$FEATURE_CHANGED" ]; then
        FAILED=1
        REASONS+=$'\n=== Missing feature documentation ===\n'
        REASONS+=$'Watched code changed but no feature doc under features/<name>/ was added or updated.\n'
        REASONS+=$'Create or update features/<feature-name>/FEATURE.md (and DEPLOY.md) per features/_TEMPLATE/.\n'
        REASONS+=$'Changed code files:\n'
        REASONS+="$CODE_CHANGED"$'\n'
    fi
fi

if [ "$FAILED" -eq 1 ]; then
    echo "Quality gate failed. Fix the following before finishing:" >&2
    echo "$REASONS" >&2
    exit 2
fi

exit 0
