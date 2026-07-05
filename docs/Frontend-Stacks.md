# 🎨 Frontend stacks

claude-kit tailors the frontend tooling, npm dependencies, scripts, and skills to your project's stack. It detects the stack, and you confirm or override it.

## How detection works

```mermaid
flowchart TD
    A{package.json<br/>has 'vue'?} -- yes --> V[inertia-vue]
    A -- no --> B{has 'react'?}
    B -- yes --> R[inertia-react]
    B -- no --> C{livewire/livewire<br/>or a package.json?}
    C -- yes --> BL[blade]
    C -- no --> N[none — API-only]
    classDef v fill:#42b883,stroke:#2f855a,color:#fff;
    classDef r fill:#61dafb,stroke:#2b6cb0,color:#000;
    classDef b fill:#f59e0b,stroke:#b45309,color:#fff;
    classDef n fill:#6b7280,stroke:#374151,color:#fff;
    class V v; class R r; class BL b; class N n;
```

Override anytime with `--stack=inertia-vue|inertia-react|blade|none`.

## What each stack installs

| Stack | Config files | npm scripts | Type check | Extra skills |
| --- | --- | --- | --- | --- |
| **inertia-vue** | `eslint.config.js`, `.prettierrc`, `tsconfig.json` | lint, format, types | `vue-tsc --noEmit` | inertia-vue-development, tailwindcss-development, wayfinder-development |
| **inertia-react** | `eslint.config.js`, `.prettierrc`, `tsconfig.json` | lint, format, types | `tsc --noEmit` | tailwindcss-development |
| **blade** | `.prettierrc` | format | — | tailwindcss-development |
| **none** | *(none)* | *(none)* | — | *(none)* |

> Base skills installed for **every** stack: `laravel-best-practices`, `pest-testing`.

## The gate is stack-aware

`quality-checks.sh` runs the frontend block **only if** your `package.json` defines the matching scripts. So one gate is correct whether you have a full Vue/React toolchain, Prettier-only Blade formatting, or no frontend at all.

## Adding a stack

Stacks are defined in one place: the `FrontendStack` enum (`src/Support/FrontendStack.php`) plus a `stubs/frontend/<dir>/` directory. See **[Architecture](Architecture)** and [CONTRIBUTING](https://github.com/mohamed-ashraf-elsaed/claude-kit/blob/main/CONTRIBUTING.md).

---
<sub>[← Configuration](Configuration) · 🏠 [Home](Home) · [Quality gate →](Quality-Gate)</sub>
