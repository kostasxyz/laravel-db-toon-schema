# Laravel DB TOON Schema

Extract your MySQL, MariaDB, or SQLite schema into JSON and a compressed TOON text format optimized for LLM consumption.

## Installation

```bash
composer require kostasch/laravel-db-toon-schema
```

## Requirements

- PHP `^8.2`
- Laravel `^12.0`
- MySQL, MariaDB, or SQLite

## Usage

### Artisan Command

```bash
# Generate using default connection
php artisan db-toon-schema

# Override connection
php artisan db-toon-schema --connection=reporting

# Override output path (relative to storage/app/private/)
php artisan db-toon-schema --output=my-schema

# Exclude specific tables or columns
php artisan db-toon-schema --exclude-tables=cache --exclude-tables=sessions
php artisan db-toon-schema --exclude-columns=created_at --exclude-columns=updated_at

# Combine options
php artisan db-toon-schema --connection=reporting --output=reporting-schema --exclude-tables=cache
```

Output files are saved to `storage/app/private/db-toon-schema/` by default:
- `schema.json` — full schema with fields and relations
- `schema.toon` — compressed single-line-per-table format

### Programmatic API

```php
use Kostasch\LaravelDbToonSchema\LaravelDbToonSchema;

$schema = app(LaravelDbToonSchema::class);

$schema->toArray();   // ['schema' => [...], 'relations' => [...]]
$schema->toJson();    // JSON string
$schema->toToon();    // TOON string
$schema->save();      // writes .json + .toon to storage
$schema->save('custom-path');
```

### Facade

```php
use Kostasch\LaravelDbToonSchema\LaravelDbToonSchemaFacade as DbToonSchema;

DbToonSchema::toArray();
DbToonSchema::toToon();
DbToonSchema::save();
```

## Configuration

Publish the config file:

```bash
php artisan vendor:publish --provider="Kostasch\LaravelDbToonSchema\LaravelDbToonSchemaServiceProvider" --tag=config
```

```php
// config/laravel-db-toon-schema.php
return [
    'connection' => null,              // null = Laravel default connection
    'excluded_tables' => [],           // tables to always skip
    'excluded_columns' => [],          // columns to always skip
    'output_path' => 'db-toon-schema', // relative to storage/app/private/
];
```

Command options (`--connection`, `--exclude-tables`, `--exclude-columns`, `--output`) override config values when provided.

## TOON Format

Each table is one line: `table_name{field:type field:type}`. Foreign keys append `>ref_table.ref_col`.

```
users{id:u64 name:str email:str}
posts{id:u64 user_id:u64>users.id title:str body:str published_at:dt}
```

### Type Mapping

| SQL Type | TOON |
|---|---|
| `bigint` | `u64` |
| `int` | `i32` |
| `mediumint` | `i24` |
| `smallint` | `i16` |
| `tinyint(1)` | `b` |
| `tinyint` | `u8` |
| `varchar`, `char`, `text`, `longtext` | `str` |
| `datetime`, `timestamp`, `date` | `dt` |
| `time` | `time` |
| `year` | `year` |
| `float` | `f32` |
| `double`, `decimal` | `f64` |
| `json` | `json` |
| `enum('a','b')` | `enum[a\|b]` |
| unknown | `unk` |

## Testing

```bash
composer test
composer format
```

## TODO

- Add real integration tests against MySQL and MariaDB for `MysqlSchemaExtractor`
- Run integration tests in CI with a MySQL/MariaDB matrix
- Keep the current fake-based suite as the default fast test layer
- Gate real DB integration tests behind an env flag for local development

## License

MIT. See [LICENSE.md](LICENSE.md).
