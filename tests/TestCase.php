<?php

declare(strict_types=1);

namespace MohamedAshrafElsaed\ClaudeKit\Tests;

use Illuminate\Foundation\Application;
use MohamedAshrafElsaed\ClaudeKit\ClaudeKitServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    /**
     * @param  Application  $app
     * @return list<class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            ClaudeKitServiceProvider::class,
        ];
    }
}
