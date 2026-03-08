<?php

namespace Kostasch\LaravelDbToonSchema\Extractors;

use Illuminate\Database\Connection;
use Kostasch\LaravelDbToonSchema\Contracts\SchemaExtractor;

final class MysqlSchemaExtractor implements SchemaExtractor
{
    public function __construct(private Connection $connection) {}

    public function extract(array $excludedTables = [], array $excludedColumns = []): array
    {
        $databaseName = $this->connection->getDatabaseName();
        $responseData = ['schema' => [], 'relations' => []];

        $tables = $this->connection->select(
            "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_TYPE = 'BASE TABLE'",
            [$databaseName]
        );

        foreach ($tables as $tableRow) {
            $table = $tableRow->TABLE_NAME;

            if (in_array($table, $excludedTables)) {
                continue;
            }

            $columns = $this->connection->select('
                SELECT
                    COLUMN_NAME,
                    DATA_TYPE,
                    COLUMN_TYPE,
                    IS_NULLABLE,
                    COLUMN_DEFAULT,
                    COLUMN_COMMENT,
                    EXTRA,
                    COLUMN_KEY,
                    CHARACTER_MAXIMUM_LENGTH,
                    NUMERIC_PRECISION,
                    NUMERIC_SCALE
                FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = ?
                AND TABLE_NAME = ?
                ORDER BY ORDINAL_POSITION
            ', [$databaseName, $table]);

            foreach ($columns as $column) {
                if (in_array($column->COLUMN_NAME, $excludedColumns)) {
                    continue;
                }

                $responseData['schema'][$table]['fields'][$column->COLUMN_NAME] = [
                    'type' => $column->COLUMN_TYPE.($column->COLUMN_KEY === 'PRI' ? ' (primary key)' : ''),
                ];
            }

            $fks = $this->connection->select('
                SELECT
                    COLUMN_NAME,
                    REFERENCED_TABLE_NAME,
                    REFERENCED_COLUMN_NAME
                FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = ?
                AND TABLE_NAME = ?
                AND REFERENCED_TABLE_NAME IS NOT NULL
            ', [$databaseName, $table]);

            foreach ($fks as $fk) {
                if (in_array($fk->COLUMN_NAME, $excludedColumns)) {
                    continue;
                }
                if (in_array($fk->REFERENCED_TABLE_NAME, $excludedTables)) {
                    continue;
                }
                if (in_array($fk->REFERENCED_COLUMN_NAME, $excludedColumns)) {
                    continue;
                }

                $responseData['relations'][] = "{$table}.{$fk->COLUMN_NAME}>{$fk->REFERENCED_TABLE_NAME}.{$fk->REFERENCED_COLUMN_NAME}";
            }
        }

        return $responseData;
    }
}
