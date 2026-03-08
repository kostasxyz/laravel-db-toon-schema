<?php

namespace Kostasch\LaravelDbToonSchema\Extractors;

use Illuminate\Database\Connection;
use Kostasch\LaravelDbToonSchema\Contracts\SchemaExtractor;

final class SqliteSchemaExtractor implements SchemaExtractor
{
    public function __construct(private Connection $connection) {}

    public function extract(array $excludedTables = [], array $excludedColumns = []): array
    {
        $responseData = ['schema' => [], 'relations' => []];

        $tables = $this->connection->select(
            "SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%' ORDER BY name"
        );

        foreach ($tables as $tableRow) {
            $table = $tableRow->name;

            if (in_array($table, $excludedTables, true)) {
                continue;
            }

            $columns = $this->connection->select(sprintf(
                'PRAGMA table_info(%s)',
                $this->quoteIdentifier($table),
            ));

            foreach ($columns as $column) {
                if (in_array($column->name, $excludedColumns, true)) {
                    continue;
                }

                $type = $this->normalizeColumnType((string) ($column->type ?? ''), (int) $column->pk > 0);

                $responseData['schema'][$table]['fields'][$column->name] = [
                    'type' => $type.((int) $column->pk > 0 ? ' (primary key)' : ''),
                ];
            }

            $foreignKeys = $this->connection->select(sprintf(
                'PRAGMA foreign_key_list(%s)',
                $this->quoteIdentifier($table),
            ));

            foreach ($foreignKeys as $foreignKey) {
                $fromColumn = (string) $foreignKey->from;
                $referencedTable = (string) $foreignKey->table;
                $referencedColumn = (string) $foreignKey->to;

                if ($referencedColumn === '') {
                    continue;
                }

                if (in_array($fromColumn, $excludedColumns, true)) {
                    continue;
                }

                if (in_array($referencedTable, $excludedTables, true)) {
                    continue;
                }

                if (in_array($referencedColumn, $excludedColumns, true)) {
                    continue;
                }

                $responseData['relations'][] = "{$table}.{$fromColumn}>{$referencedTable}.{$referencedColumn}";
            }
        }

        return $responseData;
    }

    private function normalizeColumnType(string $declaredType, bool $isPrimaryKey): string
    {
        $type = strtolower(trim($declaredType));

        if ($type === '') {
            return 'unk';
        }

        if (str_contains($type, 'json')) {
            return 'json';
        }

        if (str_contains($type, 'bool')) {
            return 'tinyint(1)';
        }

        if (str_contains($type, 'bigint')) {
            return 'bigint';
        }

        if ($type === 'integer' || str_contains($type, 'int')) {
            return $isPrimaryKey ? 'bigint' : 'int';
        }

        if (str_contains($type, 'decimal') || str_contains($type, 'numeric')) {
            return 'decimal';
        }

        if (str_contains($type, 'double')) {
            return 'double';
        }

        if (str_contains($type, 'float') || str_contains($type, 'real')) {
            return 'float';
        }

        if (str_contains($type, 'datetime') || str_contains($type, 'timestamp')) {
            return 'datetime';
        }

        if ($type === 'date') {
            return 'date';
        }

        if ($type === 'time') {
            return 'time';
        }

        if (str_contains($type, 'char') || str_contains($type, 'clob') || str_contains($type, 'text')) {
            return $type;
        }

        if (str_contains($type, 'blob')) {
            return 'blob';
        }

        return $type;
    }

    private function quoteIdentifier(string $identifier): string
    {
        return '"'.str_replace('"', '""', $identifier).'"';
    }
}
