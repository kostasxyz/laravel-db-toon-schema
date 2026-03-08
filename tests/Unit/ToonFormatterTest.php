<?php

use Kostasch\LaravelDbToonSchema\Formatters\ToonFormatter;

beforeEach(function () {
    $this->formatter = new ToonFormatter;
});

describe('type mapping', function () {
    it('maps column types to short types', function (string $input, string $expected) {
        $data = [
            'schema' => [
                'test' => [
                    'fields' => [
                        'col' => ['type' => $input],
                    ],
                ],
            ],
            'relations' => [],
        ];

        $result = $this->formatter->format($data);
        expect($result)->toBe("test{col:{$expected}}");
    })->with([
        'bigint unsigned' => ['bigint(20) unsigned', 'u64'],
        'int' => ['int(11)', 'i32'],
        'smallint' => ['smallint', 'i16'],
        'tinyint(1) boolean' => ['tinyint(1)', 'b'],
        'tinyint other' => ['tinyint(4)', 'u8'],
        'mediumint' => ['mediumint', 'i24'],
        'varchar' => ['varchar(255)', 'str'],
        'char' => ['char(36)', 'str'],
        'text' => ['text', 'str'],
        'longtext' => ['longtext', 'str'],
        'mediumtext' => ['mediumtext', 'str'],
        'datetime' => ['datetime', 'dt'],
        'timestamp' => ['timestamp', 'dt'],
        'date' => ['date', 'dt'],
        'time' => ['time', 'time'],
        'year' => ['year', 'year'],
        'float' => ['float', 'f32'],
        'double' => ['double', 'f64'],
        'decimal' => ['decimal(10,2)', 'f64'],
        'json' => ['json', 'json'],
        'unknown' => ['blob', 'unk'],
    ]);

    it('maps enum types with values', function () {
        $data = [
            'schema' => [
                'test' => [
                    'fields' => [
                        'status' => ['type' => "enum('active','inactive')"],
                    ],
                ],
            ],
            'relations' => [],
        ];

        expect($this->formatter->format($data))->toBe('test{status:enum[active|inactive]}');
    });
});

describe('toon format output', function () {
    it('formats a single table with fields', function () {
        $data = [
            'schema' => [
                'users' => [
                    'fields' => [
                        'id' => ['type' => 'bigint(20) unsigned (primary key)'],
                        'name' => ['type' => 'varchar(255)'],
                        'email' => ['type' => 'varchar(255)'],
                    ],
                ],
            ],
            'relations' => [],
        ];

        expect($this->formatter->format($data))->toBe('users{id:u64 name:str email:str}');
    });

    it('appends FK references to fields', function () {
        $data = [
            'schema' => [
                'posts' => [
                    'fields' => [
                        'id' => ['type' => 'bigint(20) unsigned'],
                        'user_id' => ['type' => 'bigint(20) unsigned'],
                    ],
                ],
            ],
            'relations' => ['posts.user_id>users.id'],
        ];

        expect($this->formatter->format($data))->toBe('posts{id:u64 user_id:u64>users.id}');
    });

    it('formats multiple tables one per line', function () {
        $data = [
            'schema' => [
                'users' => ['fields' => ['id' => ['type' => 'bigint(20) unsigned']]],
                'posts' => ['fields' => ['id' => ['type' => 'bigint(20) unsigned']]],
            ],
            'relations' => [],
        ];

        $result = $this->formatter->format($data);
        $lines = explode("\n", $result);
        expect($lines)->toHaveCount(2)
            ->and($lines[0])->toBe('users{id:u64}')
            ->and($lines[1])->toBe('posts{id:u64}');
    });

    it('returns empty string for empty schema', function () {
        expect($this->formatter->format(['schema' => [], 'relations' => []]))->toBe('');
    });

    it('skips tables with no fields', function () {
        $data = [
            'schema' => [
                'empty_table' => ['fields' => []],
                'users' => ['fields' => ['id' => ['type' => 'bigint(20) unsigned']]],
            ],
            'relations' => [],
        ];

        expect($this->formatter->format($data))->toBe('users{id:u64}');
    });
});
