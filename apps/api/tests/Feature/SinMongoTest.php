<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use MongoDB\Driver\Exception\InvalidArgumentException;

it('falla en vez de conectarse a una base de mongo real', function () {
    expect(config('database.connections.mongodb.dsn'))->toBe('invalida-en-tests');

    expect(fn () => DB::connection('mongodb')->getCollection('bitacora')->countDocuments())
        ->toThrow(InvalidArgumentException::class);
});
