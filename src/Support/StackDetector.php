<?php

declare(strict_types=1);

namespace MohamedAshrafElsaed\ClaudeKit\Support;

use Illuminate\Filesystem\Filesystem;

/**
 * Detects the host project's frontend stack from its composer.json and
 * package.json so the installer can default to the right tooling.
 */
final class StackDetector
{
    public function __construct(
        private readonly Filesystem $files,
        private readonly string $basePath,
    ) {}

    public function detect(): FrontendStack
    {
        $composer = $this->readJson($this->basePath.'/composer.json');
        $package = $this->readJson($this->basePath.'/package.json');

        $composerDependencies = array_keys(array_merge(
            $this->section($composer, 'require'),
            $this->section($composer, 'require-dev'),
        ));
        $npmDependencies = array_keys(array_merge(
            $this->section($package, 'dependencies'),
            $this->section($package, 'devDependencies'),
        ));

        if (in_array('vue', $npmDependencies, true)) {
            return FrontendStack::InertiaVue;
        }

        if (in_array('react', $npmDependencies, true)) {
            return FrontendStack::InertiaReact;
        }

        if (in_array('livewire/livewire', $composerDependencies, true) || $package !== []) {
            return FrontendStack::Blade;
        }

        return FrontendStack::None;
    }

    /**
     * @param  array<string, mixed>  $json
     * @return array<string, mixed>
     */
    private function section(array $json, string $key): array
    {
        $value = $json[$key] ?? [];

        return is_array($value) ? $value : [];
    }

    /**
     * @return array<string, mixed>
     */
    private function readJson(string $path): array
    {
        if (! $this->files->exists($path)) {
            return [];
        }

        $decoded = json_decode($this->files->get($path), true);

        return is_array($decoded) ? $decoded : [];
    }
}
