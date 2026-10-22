<?php

use App\Models\User;
use Database\Seeders\DatabaseSeeder;

use function Pest\Laravel\seed;

it('siembra un usuario de prueba por rol del panel', function () {
    seed(DatabaseSeeder::class);

    $roles = User::query()->with('roles')->get()
        ->mapWithKeys(fn (User $u) => [$u->email => $u->roles->pluck('name')->sole()])
        ->all();

    expect($roles)->toBe([
        'admin@aleacionprima.test' => 'administrador',
        'ventas@aleacionprima.test' => 'ventas',
        'encargado@aleacionprima.test' => 'encargado',
    ]);
});

it('no duplica usuarios al sembrar dos veces', function () {
    seed(DatabaseSeeder::class);
    seed(DatabaseSeeder::class);

    expect(User::query()->count())->toBe(3);
});
