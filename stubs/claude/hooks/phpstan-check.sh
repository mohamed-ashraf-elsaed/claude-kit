#!/usr/bin/env bash
#
# claude-kit PostToolUse hook: run PHPStan on the PHP file Claude just edited.
#
# Wired in .claude/settings.json under PostToolUse with matcher "Edit|Write".
# The tool has already run by the time a PostToolUse hook fires, so this cannot
# prevent the edit — what it does is decide whether Claude is told about the
# errors:
#
#   mode=block  exit 2 with the errors on stderr, so Claude sees them and has to
#               fix them before moving on.
#   mode=warn   exit 0 with a systemMessage, so you see a warning and Claude is
#               not interrupted.
#
# The level is not passed here: it comes from phpstan.neon, which claude-kit
# generated at level {{PHPSTAN_LEVEL}}. Change it there and this hook follows.

set -uo pipefail

MODE="{{PHPSTAN_MODE}}"

PROJECT_DIR="${CLAUDE_PROJECT_DIR:-$(git rev-parse --show-toplevel 2>/dev/null || pwd)}"
cd "$PROJECT_DIR" || exit 0

PAYLOAD="$(cat)"

# Pull tool_input.file_path out of the hook payload.
FILE="$(printf '%s' "$PAYLOAD" | php -r '$p=@json_decode(@file_get_contents("php://stdin"),true); echo (is_array($p)&&isset($p["tool_input"]["file_path"])&&is_string($p["tool_input"]["file_path"]))?$p["tool_input"]["file_path"]:"";' 2>/dev/null)"

# Nothing to analyse: not a PHP file, no path in the payload, or PHPStan is not
# installed/configured in this project.
[ -n "$FILE" ] || exit 0
case "$FILE" in
    *.php) ;;
    *) exit 0 ;;
esac
[ -f "$FILE" ] || exit 0
[ -f phpstan.neon ] || exit 0
[ -x vendor/bin/phpstan ] || exit 0

# Warn mode needs a reliable count, so it asks for JSON and keeps stdout clean:
# PHPStan writes its "Note: Using configuration file ..." banner to the other
# stream, and folding the two together would break the parse. Block mode wants
# text a human or an agent can read, so it keeps both streams.
if [ "$MODE" = "warn" ]; then
    if OUT="$(vendor/bin/phpstan analyse --no-progress --error-format=json "$FILE" 2>/dev/null)"; then
        exit 0
    fi
    # PHPStan's own JSON puts the number in totals.file_errors. A replacement
    # formatter such as laravel/pao emits its own valid JSON with a top-level
    # "errors" instead, so both shapes are read before falling back to counting
    # "path:line:" lines in plain text output.
    COUNT="$(printf '%s' "$OUT" | php -r '
        $d = json_decode(stream_get_contents(STDIN), true);
        if (! is_array($d)) { exit(1); }
        if (isset($d["totals"]["file_errors"])) { echo (int) $d["totals"]["file_errors"]; exit; }
        if (isset($d["errors"]) && is_numeric($d["errors"])) { echo (int) $d["errors"]; exit; }
        exit(1);
    ' 2>/dev/null)" \
        || COUNT="$(printf '%s\n' "$OUT" | grep -cE ':[0-9]+:' || true)"
    php -r 'echo json_encode(["systemMessage" => sprintf("PHPStan reported %d issue(s) in %s. Run: vendor/bin/phpstan analyse %s", (int) $argv[1], $argv[2], $argv[2])], JSON_UNESCAPED_SLASHES), PHP_EOL;' "$COUNT" "$FILE"
    exit 0
fi

if OUT="$(vendor/bin/phpstan analyse --no-progress --error-format=raw "$FILE" 2>&1)"; then
    exit 0
fi

{
    echo "PHPStan failed for $FILE. Fix these before continuing:"
    echo "$OUT"
} >&2
exit 2
