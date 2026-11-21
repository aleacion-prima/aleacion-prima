<?php

use App\Enums\EstadoLote;
use App\Enums\Rol;
use App\Imprevistos\AlmacenImprevistos;
use App\Models\Area;
use App\Models\Imprevisto;
use App\Models\Lote;
use App\Models\LoteArea;
use App\Models\Producto;
use App\Models\User;
use Database\Seeders\AreaSeeder;
use Database\Seeders\RolesSeeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Exceptions;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\seed;
use function Pest\Laravel\travelTo;

beforeEach(function () {
    seed([RolesSeeder::class, AreaSeeder::class]);
    travelTo('2026-11-02 12:00:00');
    entrarParaSemaforo(Rol::Administrador);
});

/** @param  list<string>  $areas */
function entrarParaSemaforo(Rol $rol, array $areas = ['MOL']): User
{
    $usuario = User::factory()->create();
    $usuario->assignRole($rol);
    $usuario->areas()->sync(Area::query()->whereIn('codigo', $areas)->pluck('id'));
    Sanctum::actingAs($usuario, ['*']);

    return $usuario;
}

/**
 * Lote en proceso de 100 piezas. `tardias` es cuántas capturas de la parada llegaron
 * tarde: 0 ninguna, 1 la entrada, 2 la entrada y la salida.
 *
 * @param  list<array{area: string, merma?: int, tardias?: int}>  $paradas
 */
function loteParaSemaforo(array $paradas = [['area' => 'MOL']], string $costoTotal = '0', string $precioMayoreo = '100'): Lote
{
    $producto = Producto::factory()->create(['precio_mayoreo' => $precioMayoreo]);
    $lote = Lote::factory()->enProceso()->create([
        'producto_id' => $producto->id,
        'cantidad_objetivo' => 100,
        'costo_total' => $costoTotal,
    ]);

    foreach ($paradas as $indice => $datos) {
        $merma = $datos['merma'] ?? 0;
        $tardias = $datos['tardias'] ?? 0;

        LoteArea::factory()->create([
            'lote_id' => $lote->id,
            'area_id' => Area::query()->where('codigo', $datos['area'])->value('id'),
            'orden' => $indice + 1,
            'tarifa_mano_obra_hora' => 1,
            'costo_maquina_hora' => 1,
            'piezas_recibidas' => 100,
            'piezas_entregadas' => 100 - $merma,
            'merma_piezas' => $merma,
            'entrada_ocurrido_en' => '2026-11-02 06:00',
            'entrada_registrado_en' => $tardias >= 1 ? '2026-11-02 07:00' : '2026-11-02 06:05',
            'salida_ocurrido_en' => '2026-11-02 08:00',
            'salida_registrado_en' => $tardias >= 2 ? '2026-11-02 09:00' : '2026-11-02 08:05',
        ]);
    }

    return $lote;
}

/** Abre un imprevisto por la API, en la primera parada del lote. */
function abrirImprevistoParaSemaforo(Lote $lote, string $ocurridoEn, bool $resuelto = false): void
{
    $parada = $lote->paradas()->firstOrFail();

    postJson("/api/lotes/{$lote->codigo}/paradas/{$parada->id}/imprevistos", [
        'tipo' => 'falla_maquina',
        'descripcion' => 'Falla simulada para el semáforo.',
        'ocurrido_en' => "2026-11-02 {$ocurridoEn}",
        'resuelto' => $resuelto,
    ])->assertCreated();
}

/** @return array{semaforo: string|null, motivos: list<string>} */
function semaforoDe(Lote $lote): array
{
    $json = getJson("/api/lotes/{$lote->codigo}")->assertOk()->json();

    return ['semaforo' => $json['semaforo'], 'motivos' => $json['motivos']];
}

function incompletoDe(Lote $lote): bool
{
    return (bool) getJson("/api/lotes/{$lote->codigo}")->assertOk()->json('semaforo_incompleto');
}

/** Envuelve al almacén real y cuenta cuántas veces se lee: una consulta en lote o una por lote. */
function almacenEspiaDeImprevistos(AlmacenImprevistos $real): object
{
    return new class($real) implements AlmacenImprevistos
    {
        public int $consultasPorLote = 0;

        public int $consultasEnLote = 0;

        public function __construct(private AlmacenImprevistos $real) {}

        public function guardar(array $datos): Imprevisto
        {
            return $this->real->guardar($datos);
        }

        public function delLote(int $loteId): Collection
        {
            $this->consultasPorLote++;

            return $this->real->delLote($loteId);
        }

        public function abiertosDeLotes(array $loteIds): Collection
        {
            $this->consultasEnLote++;

            return $this->real->abiertosDeLotes($loteIds);
        }

        public function buscar(int $loteId, string $id): ?Imprevisto
        {
            return $this->real->buscar($loteId, $id);
        }

        public function marcarResuelto(Imprevisto $imprevisto, bool $resuelto): Imprevisto
        {
            return $this->real->marcarResuelto($imprevisto, $resuelto);
        }
    };
}

/** Almacén que simula a Mongo caído: toda lectura y escritura lanza. */
function almacenDeImprevistosCaido(): AlmacenImprevistos
{
    return new class implements AlmacenImprevistos
    {
        public function guardar(array $datos): Imprevisto
        {
            throw new RuntimeException('Mongo no responde.');
        }

        public function delLote(int $loteId): Collection
        {
            throw new RuntimeException('Mongo no responde.');
        }

        public function abiertosDeLotes(array $loteIds): Collection
        {
            throw new RuntimeException('Mongo no responde.');
        }

        public function buscar(int $loteId, string $id): ?Imprevisto
        {
            throw new RuntimeException('Mongo no responde.');
        }

        public function marcarResuelto(Imprevisto $imprevisto, bool $resuelto): Imprevisto
        {
            throw new RuntimeException('Mongo no responde.');
        }
    };
}

it('la merma acumulada de las paradas pone el lote en verde, ámbar o rojo', function (int $primera, int $segunda, string $semaforo, array $motivos) {
    $lote = loteParaSemaforo([['area' => 'MOL', 'merma' => $primera], ['area' => 'FUS', 'merma' => $segunda]]);

    expect(semaforoDe($lote))->toBe(['semaforo' => $semaforo, 'motivos' => $motivos]);
})->with([
    'bajo el umbral' => [2, 2, 'verde', []],
    'en el umbral ámbar (5 %)' => [3, 2, 'ambar', ['Merma acumulada de 5 de 100 piezas (5.0 %).']],
    'en el umbral rojo (10 %)' => [6, 4, 'rojo', ['Merma acumulada de 10 de 100 piezas (10.0 %).']],
]);

it('los imprevistos sin resolver ponen ámbar, y rojo si llevan más de 2 h', function (?string $desde, bool $resuelto, string $semaforo, array $motivos) {
    $lote = loteParaSemaforo();

    if ($desde !== null) {
        abrirImprevistoParaSemaforo($lote, $desde, $resuelto);
    }

    expect(semaforoDe($lote))->toBe(['semaforo' => $semaforo, 'motivos' => $motivos]);
})->with([
    'sin imprevistos' => [null, false, 'verde', []],
    'uno abierto hace 30 min' => ['11:30', false, 'ambar', ['1 imprevisto sin resolver; el más antiguo lleva 30 min abierto.']],
    'uno abierto hace exactamente 2 h' => ['10:00', false, 'ambar', ['1 imprevisto sin resolver; el más antiguo lleva 2 h abierto.']],
    'uno abierto hace más de 2 h' => ['09:45', false, 'rojo', ['1 imprevisto sin resolver; el más antiguo lleva 2 h 15 min abierto.']],
    'uno resuelto hace 3 h' => ['09:00', true, 'verde', []],
]);

it('cuenta todos los imprevistos abiertos y mide el más antiguo', function () {
    $lote = loteParaSemaforo();
    abrirImprevistoParaSemaforo($lote, '11:50');
    abrirImprevistoParaSemaforo($lote, '11:00');

    expect(semaforoDe($lote))->toBe([
        'semaforo' => 'ambar',
        'motivos' => ['2 imprevistos sin resolver; el más antiguo lleva 1 h abierto.'],
    ]);
});

it('el costo por pieza proyectado contra el precio de mayoreo pone ámbar al 80 % y rojo al 100 %', function (string $costoTotal, string $semaforo, array $motivos) {
    $lote = loteParaSemaforo(costoTotal: $costoTotal);

    expect(semaforoDe($lote))->toBe(['semaforo' => $semaforo, 'motivos' => $motivos]);
})->with([
    'a 79 por pieza' => ['7900.0000', 'verde', []],
    'justo en 80 por pieza' => ['8000.0000', 'ambar', ['El costo por pieza proyectado llega al 80 % del precio de mayoreo.']],
    'apenas bajo el 100 %' => ['9999.9900', 'ambar', ['El costo por pieza proyectado llega al 80 % del precio de mayoreo.']],
    'justo en el 100 %' => ['10000.0000', 'rojo', ['El costo por pieza proyectado llega al 100 % del precio de mayoreo.']],
]);

it('proyecta el costo entre las piezas que quedan, no entre las del objetivo', function () {
    // 4 piezas de merma (4 %, sin alerta de merma): 7900 ÷ 96 = 82.29 por pieza, sobre el 80 %.
    $lote = loteParaSemaforo([['area' => 'MOL', 'merma' => 2], ['area' => 'FUS', 'merma' => 2]], costoTotal: '7900.0000');

    expect(semaforoDe($lote))->toBe([
        'semaforo' => 'ambar',
        'motivos' => ['El costo por pieza proyectado llega al 80 % del precio de mayoreo.'],
    ]);
});

it('la regla del costo solo se evalúa para quien ve costos', function (Rol $rol, string $semaforo) {
    $lote = loteParaSemaforo(costoTotal: '10000.0000');
    entrarParaSemaforo($rol);

    expect(semaforoDe($lote)['semaforo'])->toBe($semaforo);
})->with([
    'administrador' => [Rol::Administrador, 'rojo'],
    'ventas' => [Rol::Ventas, 'rojo'],
    'encargado' => [Rol::Encargado, 'verde'],
]);

it('el precio de mayoreo no sale en el semáforo, solo el color y el motivo', function () {
    // 100 piezas × 123.4567 = 12345.67: justo el 100 % del precio.
    $lote = loteParaSemaforo(costoTotal: '12345.6700', precioMayoreo: '123.4567');

    $respuesta = getJson("/api/lotes/{$lote->codigo}")->assertOk();

    expect($respuesta->json('semaforo'))->toBe('rojo')
        ->and($respuesta->json('motivos'))->toBe(['El costo por pieza proyectado llega al 100 % del precio de mayoreo.'])
        ->and($respuesta->getContent())->not->toContain('123.4567');
});

it('las capturas tardías repetidas ponen ámbar, nunca rojo', function (array $paradas, string $semaforo, array $motivos) {
    expect(semaforoDe(loteParaSemaforo($paradas)))->toBe(['semaforo' => $semaforo, 'motivos' => $motivos]);
})->with([
    'ninguna' => [[['area' => 'MOL']], 'verde', []],
    'una sola' => [[['area' => 'MOL', 'tardias' => 1]], 'verde', []],
    'dos' => [[['area' => 'MOL', 'tardias' => 2]], 'ambar', ['2 capturas tardías: se registraron con más de 30 min de retraso.']],
    'muchas' => [
        [['area' => 'MOL', 'tardias' => 2], ['area' => 'FUS', 'tardias' => 2], ['area' => 'COL', 'tardias' => 2]],
        'ambar',
        ['6 capturas tardías: se registraron con más de 30 min de retraso.'],
    ],
]);

it('el peor color gana y los motivos van del más grave al menos grave', function () {
    $lote = loteParaSemaforo([
        ['area' => 'MOL', 'merma' => 3, 'tardias' => 2],
        ['area' => 'FUS', 'merma' => 2],
    ]);
    abrirImprevistoParaSemaforo($lote, '09:45');

    expect(semaforoDe($lote))->toBe([
        'semaforo' => 'rojo',
        'motivos' => [
            '1 imprevisto sin resolver; el más antiguo lleva 2 h 15 min abierto.',
            'Merma acumulada de 5 de 100 piezas (5.0 %).',
            '2 capturas tardías: se registraron con más de 30 min de retraso.',
        ],
    ]);
});

it('un lote sin señales de alerta está en verde y sin motivos', function () {
    $lote = loteParaSemaforo();

    expect(semaforoDe($lote))->toBe(['semaforo' => 'verde', 'motivos' => []])
        ->and(incompletoDe($lote))->toBeFalse();
});

it('los lotes terminados o cancelados no tienen semáforo', function (EstadoLote $estado) {
    $lote = Lote::factory()->create(['estado' => $estado]);

    expect(semaforoDe($lote))->toBe(['semaforo' => null, 'motivos' => []])
        ->and(incompletoDe($lote))->toBeFalse();
})->with([EstadoLote::Terminado, EstadoLote::Cancelado]);

it('la lista de lotes trae el semáforo de cada uno', function () {
    $verde = loteParaSemaforo();
    $ambar = loteParaSemaforo([['area' => 'MOL', 'merma' => 5]]);

    $respuesta = getJson('/api/lotes')->assertOk();

    expect(collect($respuesta->json())->pluck('semaforo', 'codigo')->all())
        ->toBe([$ambar->codigo => 'ambar', $verde->codigo => 'verde']);
});

it('la lista pide los imprevistos abiertos de todos los lotes en una sola consulta', function () {
    $sinProblema = loteParaSemaforo();
    $conImprevisto = loteParaSemaforo();
    loteParaSemaforo();
    abrirImprevistoParaSemaforo($conImprevisto, '11:30');

    $espia = almacenEspiaDeImprevistos(app(AlmacenImprevistos::class));
    app()->instance(AlmacenImprevistos::class, $espia);

    $respuesta = getJson('/api/lotes')->assertOk()->assertJsonCount(3);
    $semaforos = collect($respuesta->json())->pluck('semaforo', 'codigo');

    expect($espia->consultasEnLote)->toBe(1)
        ->and($espia->consultasPorLote)->toBe(0)
        ->and($semaforos->get($conImprevisto->codigo))->toBe('ambar')
        ->and($semaforos->get($sinProblema->codigo))->toBe('verde');
});

it('el tablero pide los imprevistos abiertos de todos los lotes en una sola consulta', function () {
    $sinProblema = loteParaSemaforo();
    $conImprevisto = loteParaSemaforo();
    loteParaSemaforo();
    abrirImprevistoParaSemaforo($conImprevisto, '11:30');

    $espia = almacenEspiaDeImprevistos(app(AlmacenImprevistos::class));
    app()->instance(AlmacenImprevistos::class, $espia);

    $respuesta = getJson('/api/tablero')->assertOk()->assertJsonCount(3);
    $semaforos = collect($respuesta->json())->pluck('semaforo', 'codigo');

    expect($espia->consultasEnLote)->toBe(1)
        ->and($espia->consultasPorLote)->toBe(0)
        ->and($semaforos->get($conImprevisto->codigo))->toBe('ambar')
        ->and($semaforos->get($sinProblema->codigo))->toBe('verde');
});

it('si Mongo falla, el detalle, la lista y el tablero evalúan sin la señal de imprevistos y lo marcan incompleto', function () {
    $lote = loteParaSemaforo([['area' => 'MOL', 'merma' => 5]]);
    abrirImprevistoParaSemaforo($lote, '09:45');
    expect(semaforoDe($lote)['semaforo'])->toBe('rojo')
        ->and(incompletoDe($lote))->toBeFalse();

    app()->instance(AlmacenImprevistos::class, almacenDeImprevistosCaido());

    expect(semaforoDe($lote))->toBe([
        'semaforo' => 'ambar',
        'motivos' => ['Merma acumulada de 5 de 100 piezas (5.0 %).'],
    ])->and(incompletoDe($lote))->toBeTrue();

    getJson('/api/lotes')
        ->assertOk()
        ->assertJsonCount(1)
        ->assertJsonPath('0.semaforo', 'ambar')
        ->assertJsonPath('0.semaforo_incompleto', true);

    getJson('/api/tablero')
        ->assertOk()
        ->assertJsonCount(1)
        ->assertJsonPath('0.semaforo', 'ambar')
        ->assertJsonPath('0.semaforo_incompleto', true);
});

it('si Mongo falla, la captura de una salida responde igual', function () {
    $lote = Lote::factory()->enProceso()->create([
        'producto_id' => Producto::factory()->create()->id,
        'cantidad_objetivo' => 100,
    ]);
    $parada = LoteArea::factory()->create([
        'lote_id' => $lote->id,
        'area_id' => Area::query()->where('codigo', 'MOL')->value('id'),
        'orden' => 1,
        'tarifa_mano_obra_hora' => 1,
        'costo_maquina_hora' => 1,
        'piezas_recibidas' => 100,
        'piezas_entregadas' => null,
        'merma_piezas' => null,
        'entrada_ocurrido_en' => '2026-11-02 06:00',
        'entrada_registrado_en' => '2026-11-02 06:05',
        'salida_ocurrido_en' => null,
        'salida_registrado_en' => null,
    ]);

    app()->instance(AlmacenImprevistos::class, almacenDeImprevistosCaido());

    postJson("/api/lotes/{$lote->codigo}/paradas/{$parada->id}/salida", [
        'piezas_entregadas' => 100,
        'ocurrido_en' => '2026-11-02 11:00',
    ])
        ->assertOk()
        ->assertJsonPath('estado', 'por_confirmar')
        ->assertJsonPath('semaforo', 'verde')
        ->assertJsonPath('semaforo_incompleto', true);

    expect($parada->fresh()->salida_ocurrido_en)->not->toBeNull();
});

it('si Mongo falla, el error se reporta aunque la respuesta salga bien', function () {
    $lote = loteParaSemaforo();
    app()->instance(AlmacenImprevistos::class, almacenDeImprevistosCaido());
    Exceptions::fake();

    getJson("/api/lotes/{$lote->codigo}")->assertOk();

    Exceptions::assertReported(RuntimeException::class);
});
