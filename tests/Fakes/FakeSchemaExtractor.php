<?php

namespace Kostasch\LaravelDbToonSchema\Tests\Fakes;

use Kostasch\LaravelDbToonSchema\Contracts\SchemaExtractor;

final class FakeSchemaExtractor implements SchemaExtractor
{
    public function __construct(private array $data) {}

    public function extract(array $excludedTables = [], array $excludedColumns = []): array
    {
        $schema = $this->data['schema'] ?? [];
        $relations = $this->data['relations'] ?? [];

        // Filter excluded tables
        foreach ($excludedTables as $table) {
            unset($schema[$table]);
        }

        // Filter relations referencing excluded tables
        $relations = array_values(array_filter($relations, function (string $r) use ($excludedTables): bool {
            if (preg_match('/^(.+?)\.(.+?)>(.+?)\.(.+?)$/', $r, $m)) {
                if (in_array($m[1], $excludedTables) || in_array($m[3], $excludedTables)) {
                    return false;
                }
            }

            return true;
        }));

        // Filter excluded columns
        foreach ($schema as $tableName => &$tableData) {
            foreach ($excludedColumns as $column) {
                unset($tableData['fields'][$column]);
            }
        }

        // Filter relations referencing excluded columns (source or referenced)
        $relations = array_values(array_filter($relations, function (string $r) use ($excludedColumns): bool {
            if (preg_match('/^(.+?)\.(.+?)>(.+?)\.(.+?)$/', $r, $m)) {
                if (in_array($m[2], $excludedColumns) || in_array($m[4], $excludedColumns)) {
                    return false;
                }
            }

            return true;
        }));

        return ['schema' => $schema, 'relations' => $relations];
    }
}
