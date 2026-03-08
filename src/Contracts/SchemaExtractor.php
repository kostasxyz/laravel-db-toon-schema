<?php

namespace Kostasch\LaravelDbToonSchema\Contracts;

interface SchemaExtractor
{
    /**
     * @param  list<string>  $excludedTables
     * @param  list<string>  $excludedColumns
     * @return array{schema: array<string, array{fields: array<string, array{type: string}>}>, relations: list<string>}
     */
    public function extract(array $excludedTables = [], array $excludedColumns = []): array;
}
