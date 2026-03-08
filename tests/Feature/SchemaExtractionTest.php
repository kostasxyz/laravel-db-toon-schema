<?php

use Illuminate\Support\Facades\Storage;
use Kostasch\LaravelDbToonSchema\Contracts\SchemaExtractor;
use Kostasch\LaravelDbToonSchema\LaravelDbToonSchema;
use Kostasch\LaravelDbToonSchema\Tests\Fakes\FakeSchemaExtractor;

beforeEach(function () {
    $this->fakeData = [
        'schema' => [
            'users' => [
                'fields' => [
                    'id' => ['type' => 'bigint(20) unsigned (primary key)'],
                    'name' => ['type' => 'varchar(255)'],
                ],
            ],
            'posts' => [
                'fields' => [
                    'id' => ['type' => 'bigint(20) unsigned (primary key)'],
                    'user_id' => ['type' => 'bigint(20) unsigned'],
                ],
            ],
        ],
        'relations' => ['posts.user_id>users.id'],
    ];

    $this->app->bind(SchemaExtractor::class, fn () => new FakeSchemaExtractor($this->fakeData));

    Storage::fake('local');
});

it('returns expected structure from toArray', function () {
    $schema = app(LaravelDbToonSchema::class);
    $result = $schema->toArray();

    expect($result)->toHaveKeys(['schema', 'relations'])
        ->and($result['schema'])->toHaveKeys(['users', 'posts'])
        ->and($result['relations'])->toBe(['posts.user_id>users.id']);
});

it('returns valid JSON from toJson', function () {
    $schema = app(LaravelDbToonSchema::class);
    $json = $schema->toJson();
    $decoded = json_decode($json, true);

    expect($decoded)->toBe($schema->toArray());
});

it('returns expected TOON string from toToon', function () {
    $schema = app(LaravelDbToonSchema::class);
    $toon = $schema->toToon();

    expect($toon)->toContain('users{id:u64 name:str}')
        ->and($toon)->toContain('posts{id:u64 user_id:u64>users.id}');
});

it('writes both files via save', function () {
    $schema = app(LaravelDbToonSchema::class);
    $schema->save();

    Storage::disk('local')->assertExists('db-toon-schema/schema.json');
    Storage::disk('local')->assertExists('db-toon-schema/schema.toon');
});

it('passes config exclusions to extractor', function () {
    config([
        'laravel-db-toon-schema.excluded_tables' => ['posts'],
        'laravel-db-toon-schema.excluded_columns' => ['name'],
    ]);

    $schema = app(LaravelDbToonSchema::class);
    $result = $schema->toArray();

    expect($result['schema'])->not->toHaveKey('posts')
        ->and($result['schema']['users']['fields'])->not->toHaveKey('name')
        ->and($result['relations'])->toBe([]);
});

it('excluded table removes its relations from output', function () {
    config(['laravel-db-toon-schema.excluded_tables' => ['users']]);

    $schema = app(LaravelDbToonSchema::class);
    $result = $schema->toArray();

    expect($result['relations'])->toBe([]);

    $toon = $schema->toToon();
    expect($toon)->not->toContain('>users.id');
});

it('excluded source column removes its relations from output', function () {
    config(['laravel-db-toon-schema.excluded_columns' => ['user_id']]);

    $schema = app(LaravelDbToonSchema::class);
    $result = $schema->toArray();

    expect($result['relations'])->toBe([])
        ->and($result['schema']['posts']['fields'])->not->toHaveKey('user_id');
});

it('excluded referenced column removes dangling relations', function () {
    config(['laravel-db-toon-schema.excluded_columns' => ['id']]);

    $schema = app(LaravelDbToonSchema::class);
    $result = $schema->toArray();

    // id excluded from both tables
    expect($result['schema']['users']['fields'])->not->toHaveKey('id')
        ->and($result['schema']['posts']['fields'])->not->toHaveKey('id')
        // relation posts.user_id>users.id should be gone because users.id is excluded
        ->and($result['relations'])->toBe([]);

    $toon = $schema->toToon();
    expect($toon)->not->toContain('>users.id');
});
