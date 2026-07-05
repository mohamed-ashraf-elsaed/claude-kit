# Frontend stacks

claude-kit tailors the frontend tooling, npm dependencies, scripts, and skills to
your project's stack. It detects the stack from `composer.json` and `package.json`
and lets you confirm or override it.

## Detection

| Signal | Detected stack |
| --- | --- |
| `vue` in `package.json` | `inertia-vue` |
| `react` in `package.json` | `inertia-react` |
| `livewire/livewire` in composer, or a `package.json` with no JS framework | `blade` |
| none of the above | `none` (API-only) |

Override anytime with `--stack=`.

## What each stack installs

| Stack | Config files | npm scripts | Type check | Skills (in addition to base) |
| --- | --- | --- | --- | --- |
| `inertia-vue` | `eslint.config.js`, `.prettierrc`, `tsconfig.json` | lint, lint:check, format, format:check, types:check | `vue-tsc --noEmit` | inertia-vue-development, tailwindcss-development, wayfinder-development |
| `inertia-react` | `eslint.config.js`, `.prettierrc`, `tsconfig.json` | lint, lint:check, format, format:check, types:check | `tsc --noEmit` | tailwindcss-development |
| `blade` | `.prettierrc` | format, format:check | — | tailwindcss-development |
| `none` | *(none)* | *(none)* | — | *(none)* |

Base skills installed for every stack: **laravel-best-practices**, **pest-testing**.

## Stack-aware gate

`quality-checks.sh` runs the frontend block **only if** your `package.json`
defines the matching scripts. So the same gate is correct whether you have a full
Vue/React toolchain, Prettier-only Blade formatting, or no frontend at all.

## Adding or changing a stack

Stacks are defined in one place: the `FrontendStack` enum
(`src/Support/FrontendStack.php`) plus a `stubs/frontend/<dir>/` directory. See
[Architecture](Architecture) and [CONTRIBUTING](https://github.com/mohamed-ashraf-elsaed/claude-kit/blob/main/CONTRIBUTING.md).
