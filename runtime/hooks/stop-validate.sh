#!/usr/bin/env bash
#
# claude-kit Stop hook: enforce the project quality gate when Claude finishes a
# turn.
#
#   1. Full code-quality suite (PHP + frontend) via the sibling
#      quality-checks.sh: Pint, PHPStan level 7 + strict-rules, Pest + coverage,
#      and (if configured) ESLint / Prettier / type-check.
#   2. Feature documentation: any change under app/, database/, routes/, or
#      resources/js/ must be accompanied by an added/updated doc under
#      features/<name>/ (excluding the _TEMPLATE/ and README.md scaffolding).
#      Disable this gate by setting CLAUDE_KIT_FEATURE_DOCS=0.
#
# Exit code 2 blocks the stop and feeds stderr back to Claude to fix. This file
# lives in vendor/ and is referenced (not copied), so `composer update`
# propagates fixes to every project.

set -uo pipefail

# Run against the project root. Claude Code exports CLAUDE_PROJECT_DIR; fall back
# to the git top level, then the current directory.
PROJECT_DIR="${CLAUDE_PROJECT_DIR:-$(git rev-parse --show-toplevel 2>/dev/null || pwd)}"
cd "$PROJECT_DIR" || exit 0

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
QUALITY="$SCRIPT_DIR/../quality-checks.sh"

FAILED=0
REASONS=""

# --- 1. Shared quality suite (PHP + frontend) -----------------------------
if [ -f "$QUALITY" ]; then
    if ! OUT="$(bash "$QUALITY" 2>&1)"; then
        FAILED=1
        REASONS+=$'\n=== Quality checks failed ===\n'"$OUT"$'\n'
    fi
fi

# --- 2. Feature documentation gate ----------------------------------------
if [ "${CLAUDE_KIT_FEATURE_DOCS:-1}" != "0" ] && git rev-parse --verify --quiet HEAD >/dev/null 2>&1; then
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
