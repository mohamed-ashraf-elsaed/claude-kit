<?php

declare(strict_types=1);

use Illuminate\Filesystem\Filesystem;
use MohamedAshrafElsaed\ClaudeKit\Support\FrontendStack;
use MohamedAshrafElsaed\ClaudeKit\Support\StackDetector;

function writeJson(string $dir, string $file, array $data): void
{
    (new Filesystem)->put($dir.'/'.$file, json_encode($data));
}

it('detects an Inertia + Vue project from package.json', function (): void {
    $dir = temp_project();
    writeJson($dir, 'composer.json', ['require' => ['inertiajs/inertia-laravel' => '^3.0']]);
    writeJson($dir, 'package.json', ['dependencies' => ['vue' => '^3.5']]);

    expect((new StackDetector(new Filesystem, $dir))->detect())
        ->toBe(FrontendStack::InertiaVue);
});

it('detects an Inertia + React project from package.json', function (): void {
    $dir = temp_project();
    writeJson($dir, 'package.json', ['dependencies' => ['react' => '^18.0']]);

    expect((new StackDetector(new Filesystem, $dir))->detect())
        ->toBe(FrontendStack::InertiaReact);
});

it('detects a Blade/Livewire project', function (): void {
    $dir = temp_project();
    writeJson($dir, 'composer.json', ['require' => ['livewire/livewire' => '^3.0']]);

    expect((new StackDetector(new Filesystem, $dir))->detect())
        ->toBe(FrontendStack::Blade);
});

it('detects an API-only project when there is no frontend tooling', function (): void {
    $dir = temp_project();
    writeJson($dir, 'composer.json', ['require' => ['laravel/framework' => '^13.0']]);

    expect((new StackDetector(new Filesystem, $dir))->detect())
        ->toBe(FrontendStack::None);
});
