<?php

namespace Kostasch\LaravelDbToonSchema;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Kostasch\LaravelDbToonSchema\LaravelDbToonSchema
 */
class LaravelDbToonSchemaFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'laravel-db-toon-schema';
    }
}
