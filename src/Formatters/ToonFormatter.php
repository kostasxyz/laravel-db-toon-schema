<?php

namespace Kostasch\LaravelDbToonSchema\Formatters;

final class ToonFormatter
{
    /**
     * @param  array{schema: array<string, array{fields: array<string, array{type: string}>}>, relations: list<string>}  $data
     */
    public function format(array $data): string
    {
        $relationMap = [];
        foreach ($data['relations'] as $relation) {
            if (preg_match('/^(.+?)\.(.+?)>(.+?)\.(.+?)$/', $relation, $matches)) {
                $relationMap["{$matches[1]}.{$matches[2]}"] = "{$matches[3]}.{$matches[4]}";
            }
        }

        $compressed = [];

        foreach ($data['schema'] as $tableName => $tableData) {
            if (empty($tableData['fields'])) {
                continue;
            }

            $fields = [];
            foreach ($tableData['fields'] as $fieldName => $fieldData) {
                $type = str_replace(' (primary key)', '', $fieldData['type']);
                $shortType = $this->mapColumnTypeToShort($type);

                $fieldStr = "{$fieldName}:{$shortType}";
                $relationKey = "{$tableName}.{$fieldName}";
                if (isset($relationMap[$relationKey])) {
                    $fieldStr .= ">{$relationMap[$relationKey]}";
                }

                $fields[] = $fieldStr;
            }

            $compressed[] = "{$tableName}{".implode(' ', $fields).'}';
        }

        return implode("\n", $compressed);
    }

    private function mapColumnTypeToShort(string $col): string
    {
        $col = strtolower($col);

        if (str_starts_with($col, 'enum')) {
            if (preg_match('/enum\((.*?)\)/', $col, $matches)) {
                $enumValues = str_replace("'", '', $matches[1]);
                $enumValues = str_replace(',', '|', $enumValues);

                return "enum[{$enumValues}]";
            }

            return 'enum';
        }

        if (str_starts_with($col, 'bigint')) {
            return 'u64';
        }
        if (str_starts_with($col, 'mediumint')) {
            return 'i24';
        }
        if (str_starts_with($col, 'smallint')) {
            return 'i16';
        }
        if (str_starts_with($col, 'tinyint(1)')) {
            return 'b';
        }
        if (str_starts_with($col, 'tinyint')) {
            return 'u8';
        }
        if (str_starts_with($col, 'int')) {
            return 'i32';
        }

        if (str_starts_with($col, 'varchar')) {
            return 'str';
        }
        if (str_starts_with($col, 'char')) {
            return 'str';
        }
        if ($col === 'text' || str_ends_with($col, 'text')) {
            return 'str';
        }

        if (str_starts_with($col, 'datetime')) {
            return 'dt';
        }
        if (str_starts_with($col, 'timestamp')) {
            return 'dt';
        }
        if (str_starts_with($col, 'date')) {
            return 'dt';
        }
        if (str_starts_with($col, 'time')) {
            return 'time';
        }
        if (str_starts_with($col, 'year')) {
            return 'year';
        }

        if (str_starts_with($col, 'float')) {
            return 'f32';
        }
        if (str_starts_with($col, 'double')) {
            return 'f64';
        }
        if (str_starts_with($col, 'decimal')) {
            return 'f64';
        }

        if (str_starts_with($col, 'json')) {
            return 'json';
        }

        return 'unk';
    }
}
