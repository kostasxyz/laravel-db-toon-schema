<?php

use Illuminate\Database\Connection;
use Illuminate\Support\Facades\Storage;
use Kostasch\LaravelDbToonSchema\Contracts\SchemaExtractor;
use Kostasch\LaravelDbToonSchema\Extractors\MysqlSchemaExtractor;
use Kostasch\LaravelDbToonSchema\Tests\Fakes\FakeSchemaExtractor;

beforeEach(function () {
    $this->fakeData = [
        'schema' => [
            'users' => [
                'fields' => [
                    'id' => ['type' => 'bigint(20) unsigned (primary key)'],
                    'name' => ['type' => 'varchar(255)'],
                    'email' => ['type' => 'varchar(255)'],
                ],
            ],
            'posts' => [
                'fields' => [
                    'id' => ['type' => 'bigint(20) unsigned (primary key)'],
                    'user_id' => ['type' => 'bigint(20) unsigned'],
                    'title' => ['type' => 'varchar(255)'],
                ],
            ],
        ],
        'relations' => ['posts.user_id>users.id'],
    ];

    $this->app->bind(SchemaExtractor::class, fn () => new FakeSchemaExtractor($this->fakeData));

    Storage::fake('local');
});

afterEach(function () {
    Mockery::close();
});

it('exits with success status', function () {
    $this->artisan('db-toon-schema')
        ->assertExitCode(0);
});

it('outputs table and relation counts', function () {
    $this->artisan('db-toon-schema')
        ->expectsOutputToContain('Tables processed: 2')
        ->expectsOutputToContain('Relations found: 1')
        ->assertExitCode(0);
});

it('creates json file at configured output path', function () {
    $this->artisan('db-toon-schema')->assertExitCode(0);

    Storage::disk('local')->assertExists('db-toon-schema/schema.json');

    $json = json_decode(Storage::disk('local')->get('db-toon-schema/schema.json'), true);
    expect($json)->toHaveKeys(['schema', 'relations'])
        ->and($json['schema'])->toHaveKeys(['users', 'posts'])
        ->and($json['relations'])->toBe(['posts.user_id>users.id']);
});

it('creates toon file at configured output path', function () {
    $this->artisan('db-toon-schema')->assertExitCode(0);

    Storage::disk('local')->assertExists('db-toon-schema/schema.toon');

    $toon = Storage::disk('local')->get('db-toon-schema/schema.toon');
    expect($toon)->toContain('users{')
        ->and($toon)->toContain('posts{')
        ->and($toon)->toContain('user_id:u64>users.id');
});

it('respects --output option', function () {
    $this->artisan('db-toon-schema --output=custom-path')->assertExitCode(0);

    Storage::disk('local')->assertExists('custom-path/schema.json');
    Storage::disk('local')->assertExists('custom-path/schema.toon');
});

it('--exclude-tables removes table and its relations', function () {
    $this->artisan('db-toon-schema', ['--exclude-tables' => ['users']])->assertExitCode(0);

    $json = json_decode(Storage::disk('local')->get('db-toon-schema/schema.json'), true);
    expect($json['schema'])->not->toHaveKey('users')
        ->and($json['schema'])->toHaveKey('posts')
        ->and($json['relations'])->toBe([]);

    $toon = Storage::disk('local')->get('db-toon-schema/schema.toon');
    expect($toon)->not->toContain('users{')
        ->and($toon)->not->toContain('>users.id');
});

it('--exclude-columns removes column and its relations', function () {
    $this->artisan('db-toon-schema', ['--exclude-columns' => ['user_id']])->assertExitCode(0);

    $json = json_decode(Storage::disk('local')->get('db-toon-schema/schema.json'), true);
    expect($json['schema']['posts']['fields'])->not->toHaveKey('user_id')
        ->and($json['schema']['users']['fields'])->toHaveKey('name')
        ->and($json['relations'])->toBe([]);

    $toon = Storage::disk('local')->get('db-toon-schema/schema.toon');
    expect($toon)->not->toContain('user_id');
});

it('resolves the requested connection option before building the extractor', function () {
    $connection = Mockery::mock(Connection::class);
    $connection->shouldReceive('getDriverName')->once()->andReturn('mysql');
    $connection->shouldReceive('getDatabaseName')->twice()->andReturn('reporting_db');
    $connection->shouldReceive('select')
        ->twice()
        ->withArgs(function (string $query, array $bindings): bool {
            return str_contains($query, 'INFORMATION_SCHEMA.TABLES')
                && $bindings === ['reporting_db'];
        })
        ->andReturn([]);

    $databaseManager = Mockery::mock();
    $databaseManager->shouldReceive('connection')->once()->with('reporting')->andReturn($connection);

    $this->app->instance('db', $databaseManager);
    $this->app->bind(SchemaExtractor::class, function ($app): SchemaExtractor {
        $connectionName = config('laravel-db-toon-schema.connection');
        $resolvedConnection = $app['db']->connection($connectionName);

        $driver = $resolvedConnection->getDriverName();

        if (! in_array($driver, ['mysql', 'mariadb', 'sqlite'], true)) {
            throw new RuntimeException("Unsupported database driver: {$driver}. Only mysql, mariadb, and sqlite are supported.");
        }

        return new MysqlSchemaExtractor($resolvedConnection);
    });

    $this->artisan('db-toon-schema --connection=reporting')
        ->expectsOutputToContain('Tables processed: 0')
        ->expectsOutputToContain('Relations found: 0')
        ->assertExitCode(0);
});
