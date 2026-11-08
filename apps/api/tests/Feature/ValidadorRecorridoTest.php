<?php

use App\Models\Area;
use App\Recorridos\ValidadorRecorrido;
use Database\Seeders\AreaSeeder;

use function Pest\Laravel\seed;

beforeEach(function () {
    seed(AreaSeeder::class);
});

/**
 * @param  list<string>  $codigos
 */
function errorDeRecorrido(array $codigos, bool $permitirRepetir = true): ?string
{
    $ids = array_map(
        fn (string $codigo) => Area::query()->where('codigo', $codigo)->firstOrFail()->id,
        $codigos,
    );

    return app(ValidadorRecorrido::class)->error($ids, $permitirRepetir);
}

it('acepta un recorrido con las obligatorias en orden', function (array $codigos) {
    expect(errorDeRecorrido($codigos))->toBeNull();
})->with([
    'solo obligatorias' => [['MOL', 'FUS', 'COL', 'DES']],
    'con opcionales' => [['MOL', 'FUS', 'COL', 'DES', 'GRA', 'CAL']],
    'opcional en medio' => [['MOL', 'FUS', 'COL', 'CAL', 'DES']],
    'repitiendo un área' => [['MOL', 'FUS', 'COL', 'DES', 'CAL', 'GRA', 'CAL']],
]);

it('dice qué obligatoria falta', function () {
    expect(errorDeRecorrido(['MOL', 'FUS', 'DES']))->toBe('Falta el área obligatoria COL.')
        ->and(errorDeRecorrido(['MOL', 'DES']))->toBe('Faltan las áreas obligatorias FUS, COL.');
});

it('rechaza obligatorias fuera de orden', function () {
    expect(errorDeRecorrido(['MOL', 'COL', 'FUS', 'DES']))->toStartWith('Las áreas obligatorias');
});

it('rechaza áreas inactivas', function () {
    Area::query()->where('codigo', 'GRA')->update(['activo' => false]);

    expect(errorDeRecorrido(['MOL', 'FUS', 'COL', 'DES', 'GRA']))->toContain('GRA');
});

it('rechaza repetidas solo cuando no se permiten', function () {
    $recorrido = ['MOL', 'FUS', 'COL', 'DES', 'CAL', 'CAL'];

    expect(errorDeRecorrido($recorrido, permitirRepetir: true))->toBeNull()
        ->and(errorDeRecorrido($recorrido, permitirRepetir: false))->toBe('No repitas un área en el recorrido.');
});
