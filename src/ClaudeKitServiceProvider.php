<?php

declare(strict_types=1);

namespace MohamedAshrafElsaed\ClaudeKit;

use Illuminate\Support\ServiceProvider;
use MohamedAshrafElsaed\ClaudeKit\Commands\InstallCommand;
use MohamedAshrafElsaed\ClaudeKit\Commands\UpdateCommand;

final class ClaudeKitServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallCommand::class,
                UpdateCommand::class,
            ]);
        }
    }
}
