<?php

declare(strict_types=1);

namespace MohamedAshrafElsaed\ClaudeKit;

use Illuminate\Support\ServiceProvider;
use MohamedAshrafElsaed\ClaudeKit\Commands\InstallCommand;

final class ClaudeKitServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallCommand::class,
            ]);
        }
    }
}
