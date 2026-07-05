# Security Policy

## Supported versions

The latest released `0.x` minor receives security fixes. Once `1.0.0` ships,
the current MAJOR and the previous MINOR will be supported.

| Version | Supported |
| ------- | --------- |
| latest `0.x` | ✅ |
| older | ❌ |

## Reporting a vulnerability

**Please do not open public issues for security vulnerabilities.**

Report privately using one of:

- GitHub's [private vulnerability reporting](https://github.com/mohamed-ashraf-elsaed/claude-kit/security/advisories/new)
  (preferred), or
- email **m.ashraf.saed@gmail.com** with the details and steps to reproduce.

You can expect an acknowledgement within 72 hours. Once the issue is confirmed
and a fix is ready, we will publish a patched release and a GitHub Security
Advisory crediting the reporter (unless you prefer to remain anonymous).

## Scope note

`claude-kit` writes files into a project and installs a git hook and a Claude
Code Stop hook that execute shell commands (linters, static analysis, tests) on
your machine and in CI. Review the scaffolded `.githooks/pre-commit` and
`.claude/settings.json` before enabling them, as you would any developer tool.
