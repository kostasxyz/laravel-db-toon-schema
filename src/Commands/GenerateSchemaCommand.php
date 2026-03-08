<?php

namespace Kostasch\LaravelDbToonSchema\Commands;

use Illuminate\Console\Command;
use Kostasch\LaravelDbToonSchema\LaravelDbToonSchema;

class GenerateSchemaCommand extends Command
{
    protected $signature = 'db-toon-schema
        {--connection= : Database connection to use}
        {--output= : Output path override}
        {--exclude-tables=* : Tables to exclude (overrides config)}
        {--exclude-columns=* : Columns to exclude (overrides config)}';

    protected $description = 'Generate database schema in JSON and TOON format';

    public function handle(): int
    {
        $this->info('Generating database schema...');

        if ($connection = $this->option('connection')) {
            config(['laravel-db-toon-schema.connection' => $connection]);
        }

        $excludeTables = $this->option('exclude-tables');
        if (! empty($excludeTables)) {
            config(['laravel-db-toon-schema.excluded_tables' => $excludeTables]);
        }

        $excludeColumns = $this->option('exclude-columns');
        if (! empty($excludeColumns)) {
            config(['laravel-db-toon-schema.excluded_columns' => $excludeColumns]);
        }

        // Resolve after config overrides so connection/exclusions take effect
        $schema = app(LaravelDbToonSchema::class);

        $outputPath = $this->option('output');

        $data = $schema->toArray();
        $tableCount = count($data['schema']);
        $relationCount = count($data['relations']);

        $schema->save($outputPath);

        $path = $outputPath ?? config('laravel-db-toon-schema.output_path', 'db-toon-schema');
        $this->info("Tables processed: {$tableCount}");
        $this->info("Relations found: {$relationCount}");
        $this->info("Schema saved to: storage/app/private/{$path}/");

        return self::SUCCESS;
    }
}
