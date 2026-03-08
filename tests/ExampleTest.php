<?php

use Illuminate\Support\ServiceProvider;
use Kostasch\LaravelDbToonSchema\Contracts\SchemaExtractor;
use Kostasch\LaravelDbToonSchema\LaravelDbToonSchema;
use Kostasch\LaravelDbToonSchema\LaravelDbToonSchemaFacade;
use Kostasch\LaravelDbToonSchema\LaravelDbToonSchemaServiceProvider;
use Kostasch\LaravelDbToonSchema\Tests\Fakes\FakeSchemaExtractor;

it('registers the package service provider', function () {
    expect($this->app->getProvider(LaravelDbToonSchemaServiceProvider::class))
        ->toBeInstanceOf(LaravelDbToonSchemaServiceProvider::class);
});

it('can resolve the main class from the container', function () {
    $this->app->bind(SchemaExtractor::class, fn () => new FakeSchemaExtractor(['schema' => [], 'relations' => []]));

    expect(app('laravel-db-toon-schema'))->toBeInstanceOf(LaravelDbToonSchema::class);
});

it('resolves the facade root from the container', function () {
    $this->app->bind(SchemaExtractor::class, fn () => new FakeSchemaExtractor(['schema' => [], 'relations' => []]));

    expect(LaravelDbToonSchemaFacade::getFacadeRoot())->toBeInstanceOf(LaravelDbToonSchema::class);
});

it('merges the package config', function () {
    expect(config('laravel-db-toon-schema'))->toHaveKeys([
        'connection', 'excluded_tables', 'excluded_columns', 'output_path',
    ]);
});

it('registers the config publish path', function () {
    $paths = ServiceProvider::pathsToPublish(LaravelDbToonSchemaServiceProvider::class, 'config');

    expect($paths)->toBe([
        dirname(__DIR__).'/src/../config/config.php' => config_path('laravel-db-toon-schema.php'),
    ]);
});
