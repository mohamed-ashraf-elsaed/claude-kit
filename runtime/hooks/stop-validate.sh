#!/usr/bin/env bash
#
# claude-kit Stop hook: enforce the quality gate when Claude finishes a turn.
#
#   1. The shared quality suite (Pint / PHPStan / Tests / Frontend) via the
#      sibling quality-checks.sh.
#   2. Feature docs: any change under app/, database/, routes/, or resources/js/
#      must be accompanied by an added/updated doc under features/<name>/.
#      Controlled by CLAUDE_KIT_FEATURE_DOCS (env) or the .claude-kit.json
#      manifest (hooks.feature_docs); defaults on.
#
# Exit code 2 blocks the stop and feeds stderr back to Claude. Lives in vendor/
# and is referenced, so `composer update` propagates fixes.

set -uo pipefail

PROJECT_DIR="${CLAUDE_PROJECT_DIR:-$(git rev-parse --show-toplevel 2>/dev/null || pwd)}"
cd "$PROJECT_DIR" || exit 0

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
QUALITY="$SCRIPT_DIR/../quality-checks.sh"

FAILED=0
REASONS=""

# --- 1. Shared quality suite ----------------------------------------------
if [ -f "$QUALITY" ] && ! OUT="$(bash "$QUALITY" 2>&1)"; then
    FAILED=1
    REASONS+=$'\n=== Quality checks failed ===\n'"$OUT"$'\n'
fi

# --- 2. Feature documentation gate ----------------------------------------
FEATURE_DOCS="${CLAUDE_KIT_FEATURE_DOCS:-}"
if [ -z "$FEATURE_DOCS" ]; then
    if [ "$(php -r '$c=@json_decode(@file_get_contents(".claude-kit.json"),true); echo (is_array($c)&&isset($c["hooks"]["feature_docs"])&&$c["hooks"]["feature_docs"]===false)?"0":"1";' 2>/dev/null)" = "0" ]; then
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
