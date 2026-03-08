<?php

namespace Kostasch\LaravelDbToonSchema;

use Kostasch\LaravelDbToonSchema\Contracts\SchemaExtractor;
use Kostasch\LaravelDbToonSchema\Formatters\ToonFormatter;

final class LaravelDbToonSchema
{
    public function __construct(private SchemaExtractor $extractor) {}

    /**
     * @return array{schema: array, relations: list<string>}
     */
    public function toArray(): array
    {
        return $this->extractor->extract(
            config('laravel-db-toon-schema.excluded_tables', []),
            config('laravel-db-toon-schema.excluded_columns', []),
        );
    }

    public function toJson(int $flags = 0): string
    {
        return json_encode($this->toArray(), $flags | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function toToon(): string
    {
        return (new ToonFormatter)->format($this->toArray());
    }

    public function save(?string $path = null): void
    {
        $path = $path ?? config('laravel-db-toon-schema.output_path', 'db-toon-schema');

        $data = $this->toArray();

        $storage = app('filesystem')->disk('local');
        $storage->put("{$path}/schema.json", json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
        $storage->put("{$path}/schema.toon", (new ToonFormatter)->format($data));
    }
}
