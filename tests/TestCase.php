<?php

namespace Kostasch\LaravelDbToonSchema\Tests;

use Kostasch\LaravelDbToonSchema\LaravelDbToonSchemaServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            LaravelDbToonSchemaServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app): array
    {
        return [
            'LaravelDbToonSchema' => \Kostasch\LaravelDbToonSchema\LaravelDbToonSchemaFacade::class,
        ];
    }
}
