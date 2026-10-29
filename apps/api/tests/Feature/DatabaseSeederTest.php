<?php

use App\Enums\TipoMovimientoInsumo;
use App\Models\Insumo;
use App\Models\MovimientoInsumo;
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

it('siembra las entradas iniciales de insumos como movimientos', function () {
    seed(DatabaseSeeder::class);

    $insumos = Insumo::query()->with('movimientos')->get();

    expect($insumos)->toHaveCount(7);

    foreach ($insumos as $insumo) {
        expect($insumo->movimientos)->toHaveCount(1)
            ->and($insumo->movimientos->first()->tipo)->toBe(TipoMovimientoInsumo::Entrada)
            ->and($insumo->movimientos->first()->proveedor)->not->toBeNull()
            ->and((float) $insumo->existencia)->toBe((float) $insumo->movimientos->sum('cantidad'));
    }

    $chatarra = Insumo::query()->where('codigo', 'CHT-FUN')->sole();

    expect((float) $chatarra->existencia)->toBe(12000.0)
        ->and($chatarra->movimientos->first()->lote_proveedor)->toBeNull();
});

it('no duplica las entradas iniciales al sembrar dos veces', function () {
    seed(DatabaseSeeder::class);
    seed(DatabaseSeeder::class);

    expect(Insumo::query()->count())->toBe(7)
        ->and(MovimientoInsumo::query()->count())->toBe(7);
});
