<?php

declare(strict_types=1);

namespace MohamedAshrafElsaed\ClaudeKit\Support;

/**
 * The frontend stack of the host project. Drives which config files, npm
 * dependencies, scripts, skills, and CLAUDE.md prose claude-kit installs.
 */
enum FrontendStack: string
{
    case InertiaVue = 'inertia-vue';
    case InertiaReact = 'inertia-react';
    case Livewire = 'livewire';
    case Blade = 'blade';
    case None = 'none';

    public function label(): string
    {
        return match ($this) {
            self::InertiaVue => 'Inertia + Vue 3 + TypeScript',
            self::InertiaReact => 'Inertia + React + TypeScript',
            self::Livewire => 'Livewire + Blade',
            self::Blade => 'Blade (server-rendered, no Livewire)',
            self::None => 'API-only (no frontend)',
        };
    }

    public function hasFrontendTooling(): bool
    {
        return $this !== self::None;
    }

    /**
     * Directory under stubs/frontend/ holding this stack's config files, or
     * null when the stack ships no frontend tooling.
     */
    public function stubDirectory(): ?string
    {
        return match ($this) {
            self::InertiaVue => 'inertia-vue',
            self::InertiaReact => 'inertia-react',
            self::Livewire, self::Blade => 'blade',
            self::None => null,
        };
    }

    /**
     * The path-scoped rule published as `.claude/rules/frontend.md`, or null when
     * the stack has no frontend code to scope rules to.
     */
    public function ruleStub(): ?string
    {
        return match ($this) {
            self::InertiaVue => 'claude/rules/frontend-inertia-vue.md',
            self::InertiaReact => 'claude/rules/frontend-inertia-react.md',
            self::Livewire => 'claude/rules/frontend-livewire.md',
            self::Blade => 'claude/rules/frontend-blade.md',
            self::None => null,
        };
    }

    /**
     * The skills to publish: two baseline Laravel skills plus stack-specific
     * ones.
     *
     * @return list<string>
     */
    public function skills(): array
    {
        $base = ['laravel-best-practices', 'pest-testing'];

        return match ($this) {
            self::InertiaVue => [...$base, 'inertia-vue-development', 'tailwindcss-development', 'wayfinder-development'],
            self::InertiaReact => [...$base, 'tailwindcss-development'],
            self::Livewire, self::Blade => [...$base, 'tailwindcss-development'],
            self::None => $base,
        };
    }

    /**
     * npm scripts merged into the host package.json.
     *
     * @return array<string, string>
     */
    public function npmScripts(): array
    {
        $lint = [
            'lint' => 'eslint . --fix',
            'lint:check' => 'eslint .',
        ];
        $format = [
            'format' => 'prettier --write resources/',
            'format:check' => 'prettier --check resources/',
        ];

        return match ($this) {
            self::InertiaVue => [...$lint, ...$format, 'types:check' => 'vue-tsc --noEmit'],
            self::InertiaReact => [...$lint, ...$format, 'types:check' => 'tsc --noEmit'],
            self::Livewire, self::Blade => $format,
            self::None => [],
        };
    }

    /**
     * devDependencies merged into the host package.json.
     *
     * @return array<string, string>
     */
    public function devDependencies(): array
    {
        $prettier = [
            'prettier' => '^3.4.2',
            'prettier-plugin-tailwindcss' => '^0.6.11',
        ];
        $eslintShared = [
            '@stylistic/eslint-plugin' => '^5.10.0',
            '@types/node' => '^22.13.5',
            'eslint' => '^9.17.0',
            'eslint-config-prettier' => '^10.0.1',
            'eslint-import-resolver-typescript' => '^4.4.4',
            'eslint-plugin-import' => '^2.32.0',
            'typescript' => '^5.2.2',
            'typescript-eslint' => '^8.23.0',
        ];

        return match ($this) {
            self::InertiaVue => [
                ...$prettier,
                ...$eslintShared,
                '@vue/eslint-config-typescript' => '^14.3.0',
                'eslint-plugin-vue' => '^9.32.0',
                'vue-tsc' => '^2.2.4',
            ],
            self::InertiaReact => [
                ...$prettier,
                ...$eslintShared,
                '@eslint/js' => '^9.19.0',
                '@types/react' => '^18.3.0',
                '@types/react-dom' => '^18.3.0',
                'eslint-plugin-react' => '^7.37.0',
                'eslint-plugin-react-hooks' => '^5.1.0',
            ],
            self::Livewire, self::Blade => $prettier,
            self::None => [],
        };
    }

    /**
     * Prose injected into the "Frontend" subsection of the generated CLAUDE.md.
     */
    public function claudeRules(): string
    {
        return match ($this) {
            self::InertiaVue => "- The app uses **Vue 3 + Inertia + TypeScript**. All `.vue`/`.ts` code must\n  pass **ESLint**, **Prettier**, and **`vue-tsc`** — part of the shared gate,\n  so they block commits, turns, and CI just like the PHP checks. Component\n  conventions live in `.claude/rules/frontend.md`.",
            self::InertiaReact => "- The app uses **React + Inertia + TypeScript**. All `.tsx`/`.ts` code must\n  pass **ESLint** (incl. `react-hooks`), **Prettier**, and **`tsc`** — part of\n  the shared gate, so they block commits, turns, and CI just like the PHP\n  checks. Component conventions live in `.claude/rules/frontend.md`.",
            self::Livewire => "- The app renders with **Livewire + Blade**. Markup is formatted with\n  **Prettier** (part of the shared gate). Component conventions live in\n  `.claude/rules/frontend.md`.",
            self::Blade => "- The app renders with **server-side Blade**. Markup and assets are formatted\n  with **Prettier** (part of the shared gate). Template conventions live in\n  `.claude/rules/frontend.md`.",
            self::None => "- This is an **API-only** project: there is no frontend build step, so the\n  shared quality gate runs the PHP checks only.",
        };
    }
}
