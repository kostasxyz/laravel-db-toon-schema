<?php

use Illuminate\Support\Facades\DB;
use Kostasch\LaravelDbToonSchema\LaravelDbToonSchema;

beforeEach(function () {
    $this->sqliteDatabasePath = tempnam(sys_get_temp_dir(), 'db-toon-schema-sqlite-');

    config([
        'database.default' => 'sqlite_testing',
        'database.connections.sqlite_testing' => [
            'driver' => 'sqlite',
            'database' => $this->sqliteDatabasePath,
            'prefix' => '',
            'foreign_key_constraints' => true,
        ],
        'laravel-db-toon-schema.connection' => 'sqlite_testing',
        'laravel-db-toon-schema.excluded_tables' => [],
        'laravel-db-toon-schema.excluded_columns' => [],
    ]);

    DB::purge('sqlite_testing');

    $connection = DB::connection('sqlite_testing');
    $connection->statement('PRAGMA foreign_keys = ON');
    $connection->statement('CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL, settings JSON)');
    $connection->statement('CREATE TABLE posts (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER NOT NULL, title TEXT NOT NULL, is_published BOOLEAN NOT NULL DEFAULT 0, FOREIGN KEY (user_id) REFERENCES users(id))');
});

afterEach(function () {
    DB::purge('sqlite_testing');

    if (isset($this->sqliteDatabasePath) && is_file($this->sqliteDatabasePath)) {
        unlink($this->sqliteDatabasePath);
    }
});

it('extracts schema and relations from a real sqlite database', function () {
    $schema = app(LaravelDbToonSchema::class);

    $result = $schema->toArray();

    expect($result['schema'])->toHaveKeys(['posts', 'users'])
        ->and($result['schema']['users']['fields'])->toHaveKeys(['id', 'name', 'settings'])
        ->and($result['schema']['users']['fields']['id']['type'])->toBe('bigint (primary key)')
        ->and($result['schema']['users']['fields']['settings']['type'])->toBe('json')
        ->and($result['schema']['posts']['fields'])->toHaveKeys(['id', 'user_id', 'title', 'is_published'])
        ->and($result['schema']['posts']['fields']['is_published']['type'])->toBe('tinyint(1)')
        ->and($result['relations'])->toBe(['posts.user_id>users.id']);

    $toon = $schema->toToon();

    expect($toon)->toContain('users{id:u64 name:str settings:json}')
        ->and($toon)->toContain('posts{id:u64 user_id:i32>users.id title:str is_published:b}');
});

it('removes dangling sqlite relations when referenced columns are excluded', function () {
    config(['laravel-db-toon-schema.excluded_columns' => ['id']]);

    $schema = app(LaravelDbToonSchema::class);
    $result = $schema->toArray();

    expect($result['schema']['users']['fields'])->not->toHaveKey('id')
        ->and($result['schema']['posts']['fields'])->not->toHaveKey('id')
        ->and($result['relations'])->toBe([]);
});
