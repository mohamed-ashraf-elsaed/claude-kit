#!/usr/bin/env bash
#
# Stop hook for developing claude-kit itself: runs the repo quality gate
# (Pint, PHPStan, Pest) and the changelog rule via bin/quality-checks.sh.
# Exit code 2 blocks the stop and feeds the report back to Claude.

set -uo pipefail

PROJECT_DIR="${CLAUDE_PROJECT_DIR:-$(git rev-parse --show-toplevel 2>/dev/null || pwd)}"
cd "$PROJECT_DIR" || exit 0

if ! OUT="$(bash bin/quality-checks.sh 2>&1)"; then
    echo "Quality gate failed. Fix the following before finishing:" >&2
    echo "$OUT" >&2
    exit 2
fi

exit 0
