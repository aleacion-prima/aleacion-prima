<?php

use Database\Seeders\RolesSeeder;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\seed;

it('crea los cuatro roles del sistema', function () {
    seed(RolesSeeder::class);

    expect(Role::query()->pluck('name')->sort()->values()->all())
        ->toBe(['administrador', 'cliente', 'encargado', 'ventas']);
});

it('es idempotente', function () {
    seed(RolesSeeder::class);
    seed(RolesSeeder::class);

    expect(Role::query()->count())->toBe(4);
});
