<?php

namespace Kostasch\LaravelDbToonSchema;

use Illuminate\Support\ServiceProvider;
use Kostasch\LaravelDbToonSchema\Commands\GenerateSchemaCommand;
use Kostasch\LaravelDbToonSchema\Contracts\SchemaExtractor;
use Kostasch\LaravelDbToonSchema\Extractors\MysqlSchemaExtractor;
use Kostasch\LaravelDbToonSchema\Extractors\SqliteSchemaExtractor;

class LaravelDbToonSchemaServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/config.php' => config_path('laravel-db-toon-schema.php'),
            ], 'config');

            $this->commands([
                GenerateSchemaCommand::class,
            ]);
        }
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/config.php', 'laravel-db-toon-schema');

        $this->app->bind(SchemaExtractor::class, function ($app): SchemaExtractor {
            $connectionName = config('laravel-db-toon-schema.connection');
            $connection = $app['db']->connection($connectionName);

            $driver = $connection->getDriverName();

            return match ($driver) {
                'mysql', 'mariadb' => new MysqlSchemaExtractor($connection),
                'sqlite' => new SqliteSchemaExtractor($connection),
                default => throw new \RuntimeException("Unsupported database driver: {$driver}. Only mysql, mariadb, and sqlite are supported."),
            };
        });

        $this->app->bind('laravel-db-toon-schema', function ($app): LaravelDbToonSchema {
            return new LaravelDbToonSchema($app->make(SchemaExtractor::class));
        });

        $this->app->alias('laravel-db-toon-schema', LaravelDbToonSchema::class);
    }
}
