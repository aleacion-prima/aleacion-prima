<?php

use App\Enums\Rol;
use App\Events\ImprevistoRegistrado;
use App\Events\LoteActualizado;
use App\Imprevistos\AlmacenImprevistos;
use App\Imprevistos\AlmacenImprevistosFake;
use App\Models\Area;
use App\Models\Lote;
use App\Models\LoteArea;
use App\Models\User;
use App\Notifications\ImprevistoRegistrado as NotificacionImprevisto;
use Carbon\CarbonImmutable;
use Database\Seeders\AreaSeeder;
use Database\Seeders\RolesSeeder;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\getJson;
use function Pest\Laravel\patchJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\seed;
use function Pest\Laravel\travelTo;

beforeEach(function () {
    seed([RolesSeeder::class, AreaSeeder::class]);
    travelTo('2026-11-02 08:00:00');
});

function entrarParaImprevistos(Rol $rol, array $areas = []): User
{
    $usuario = User::factory()->create();
    $usuario->assignRole($rol);
    $usuario->areas()->sync(Area::query()->whereIn('codigo', $areas)->pluck('id'));
    Sanctum::actingAs($usuario, ['*']);

    return $usuario;
}

function loteParaImprevistos(): Lote
{
    $lote = Lote::factory()->enProceso()->create();

    foreach (['MOL', 'FUS', 'COL', 'DES'] as $indice => $codigo) {
        LoteArea::factory()->create([
            'lote_id' => $lote->id,
            'area_id' => Area::query()->where('codigo', $codigo)->value('id'),
            'orden' => $indice + 1,
            'tarifa_mano_obra_hora' => 180,
            'costo_maquina_hora' => 220,
            'entrada_ocurrido_en' => $codigo === 'DES' ? null : '2026-11-02 06:00',
        ]);
    }

    return $lote;
}

function paradaParaImprevistos(Lote $lote, string $codigo): LoteArea
{
    return $lote->paradas()->whereHas('area', fn ($area) => $area->where('codigo', $codigo))->firstOrFail();
}

function rutaDeImprevistos(Lote $lote, LoteArea $parada): string
{
    return "/api/lotes/{$lote->codigo}/paradas/{$parada->id}/imprevistos";
}

/**
 * @param  array<string, mixed>  $cambios
 * @return array<string, mixed>
 */
function cuerpoDeImprevisto(array $cambios = []): array
{
    return [
        'tipo' => 'falla_maquina',
        'descripcion' => 'Caída de tensión; el horno perdió potencia 40 min.',
        'maquina' => 'Horno de inducción 750 kg',
        'duracion_minutos' => 40,
        'ocurrido_en' => '2026-11-02 07:20',
        ...$cambios,
    ];
}

it('registra un imprevisto en una parada y aparece en el detalle del lote', function () {
    $admin = entrarParaImprevistos(Rol::Administrador);
    $lote = loteParaImprevistos();

    postJson(rutaDeImprevistos($lote, paradaParaImprevistos($lote, 'FUS')), cuerpoDeImprevisto())
        ->assertCreated()
        ->assertJsonPath('tipo', 'falla_maquina')
        ->assertJsonPath('area.codigo', 'FUS')
        ->assertJsonPath('maquina', 'Horno de inducción 750 kg')
        ->assertJsonPath('duracion_minutos', 40)
        ->assertJsonPath('piezas_afectadas', null)
        ->assertJsonPath('resuelto', false)
        ->assertJsonPath('registrado_por', $admin->name);

    getJson("/api/lotes/{$lote->codigo}/imprevistos")
        ->assertOk()
        ->assertJsonCount(1)
        ->assertJsonPath('0.area.codigo', 'FUS')
        ->assertJsonPath('0.descripcion', 'Caída de tensión; el horno perdió potencia 40 min.');
});

it('acepta marcarlo como resuelto desde el alta', function () {
    entrarParaImprevistos(Rol::Administrador);
    $lote = loteParaImprevistos();

    postJson(rutaDeImprevistos($lote, paradaParaImprevistos($lote, 'FUS')), cuerpoDeImprevisto(['resuelto' => true]))
        ->assertCreated()
        ->assertJsonPath('resuelto', true);
});

it('pieza_defectuosa no toca lote_areas', function () {
    entrarParaImprevistos(Rol::Administrador);
    $lote = loteParaImprevistos();
    $parada = paradaParaImprevistos($lote, 'MOL');
    $campos = ['piezas_recibidas', 'piezas_entregadas', 'merma_piezas', 'merma_material', 'retorno_kg'];
    $antes = $parada->fresh()?->only($campos);

    postJson(rutaDeImprevistos($lote, $parada), cuerpoDeImprevisto([
        'tipo' => 'pieza_defectuosa',
        'piezas_afectadas' => 3,
    ]))
        ->assertCreated()
        ->assertJsonPath('piezas_afectadas', 3);

    expect($parada->fresh()?->only($campos))->toBe($antes);
});

it('deja registrar al encargado solo en sus áreas', function () {
    $lote = loteParaImprevistos();
    entrarParaImprevistos(Rol::Encargado, ['MOL']);

    postJson(rutaDeImprevistos($lote, paradaParaImprevistos($lote, 'MOL')), cuerpoDeImprevisto())
        ->assertCreated();

    postJson(rutaDeImprevistos($lote, paradaParaImprevistos($lote, 'FUS')), cuerpoDeImprevisto())
        ->assertForbidden();

    getJson("/api/lotes/{$lote->codigo}/imprevistos")->assertOk()->assertJsonCount(1);
});

it('rechaza a ventas y paradas de otro lote', function () {
    $lote = loteParaImprevistos();
    $otro = loteParaImprevistos();

    entrarParaImprevistos(Rol::Ventas);
    postJson(rutaDeImprevistos($lote, paradaParaImprevistos($lote, 'MOL')), cuerpoDeImprevisto())
        ->assertForbidden();
    getJson("/api/lotes/{$lote->codigo}/imprevistos")->assertOk();

    entrarParaImprevistos(Rol::Administrador);
    postJson(rutaDeImprevistos($lote, paradaParaImprevistos($otro, 'MOL')), cuerpoDeImprevisto())
        ->assertNotFound();
});

it('no deja registrar en un área que aún no empieza', function () {
    entrarParaImprevistos(Rol::Administrador);
    $lote = loteParaImprevistos();

    postJson(rutaDeImprevistos($lote, paradaParaImprevistos($lote, 'DES')), cuerpoDeImprevisto())
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['parada']);

    getJson("/api/lotes/{$lote->codigo}/imprevistos")->assertJsonCount(0);
});

it('responde 422 con un tipo fuera del catálogo o una hora futura', function () {
    entrarParaImprevistos(Rol::Administrador);
    $lote = loteParaImprevistos();
    $parada = paradaParaImprevistos($lote, 'MOL');

    postJson(rutaDeImprevistos($lote, $parada), cuerpoDeImprevisto(['tipo' => 'explosion']))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['tipo']);

    postJson(rutaDeImprevistos($lote, $parada), cuerpoDeImprevisto(['ocurrido_en' => '2026-11-02 09:00']))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['ocurrido_en']);

    postJson(rutaDeImprevistos($lote, $parada), cuerpoDeImprevisto(['descripcion' => '']))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['descripcion']);

    getJson("/api/lotes/{$lote->codigo}/imprevistos")->assertJsonCount(0);
});

it('toma la hora actual si no se envía ocurrido_en', function () {
    entrarParaImprevistos(Rol::Administrador);
    $lote = loteParaImprevistos();

    $respuesta = postJson(
        rutaDeImprevistos($lote, paradaParaImprevistos($lote, 'MOL')),
        collect(cuerpoDeImprevisto())->except('ocurrido_en')->all(),
    )->assertCreated();

    expect(CarbonImmutable::parse($respuesta->json('ocurrido_en'))->getTimestamp())
        ->toBe(now()->getTimestamp());
});

it('marca un imprevisto como resuelto', function () {
    entrarParaImprevistos(Rol::Administrador);
    $lote = loteParaImprevistos();
    $id = postJson(rutaDeImprevistos($lote, paradaParaImprevistos($lote, 'FUS')), cuerpoDeImprevisto())
        ->json('id');

    patchJson("/api/lotes/{$lote->codigo}/imprevistos/{$id}", ['resuelto' => true])
        ->assertOk()
        ->assertJsonPath('resuelto', true);

    getJson("/api/lotes/{$lote->codigo}/imprevistos")->assertJsonPath('0.resuelto', true);

    patchJson("/api/lotes/{$lote->codigo}/imprevistos/{$id}", [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['resuelto']);
});

it('solo deja resolver al administrador o al encargado de esa área', function () {
    $lote = loteParaImprevistos();
    entrarParaImprevistos(Rol::Administrador);
    $id = postJson(rutaDeImprevistos($lote, paradaParaImprevistos($lote, 'FUS')), cuerpoDeImprevisto())
        ->json('id');

    entrarParaImprevistos(Rol::Encargado, ['MOL']);
    patchJson("/api/lotes/{$lote->codigo}/imprevistos/{$id}", ['resuelto' => true])->assertForbidden();

    entrarParaImprevistos(Rol::Encargado, ['FUS']);
    patchJson("/api/lotes/{$lote->codigo}/imprevistos/{$id}", ['resuelto' => true])->assertOk();

    entrarParaImprevistos(Rol::Ventas);
    patchJson("/api/lotes/{$lote->codigo}/imprevistos/{$id}", ['resuelto' => false])->assertForbidden();
});

it('no encuentra imprevistos que no existen o son de otro lote', function () {
    $lote = loteParaImprevistos();
    $otro = loteParaImprevistos();
    entrarParaImprevistos(Rol::Administrador);
    $id = postJson(rutaDeImprevistos($lote, paradaParaImprevistos($lote, 'FUS')), cuerpoDeImprevisto())
        ->json('id');

    patchJson("/api/lotes/{$otro->codigo}/imprevistos/{$id}", ['resuelto' => true])->assertNotFound();
    patchJson("/api/lotes/{$lote->codigo}/imprevistos/0123456789abcdef01234567", ['resuelto' => true])
        ->assertNotFound();
});

it('avisa al tablero con ImprevistoRegistrado', function () {
    Event::fake([ImprevistoRegistrado::class]);
    entrarParaImprevistos(Rol::Administrador);
    $lote = loteParaImprevistos();
    $parada = paradaParaImprevistos($lote, 'FUS');

    postJson(rutaDeImprevistos($lote, $parada), cuerpoDeImprevisto())->assertCreated();

    Event::assertDispatchedTimes(ImprevistoRegistrado::class, 1);
    Event::assertDispatched(
        ImprevistoRegistrado::class,
        function (ImprevistoRegistrado $evento) use ($lote, $parada): bool {
            $canales = collect($evento->broadcastOn())->map(fn ($canal) => $canal->name)->all();

            return $evento->codigoLote === $lote->codigo
                && $evento->areaId === $parada->area_id
                && $evento->tipo === 'falla_maquina'
                && in_array('private-tablero', $canales, true);
        },
    );
});

it('avisa con LoteActualizado al registrar y al resolver, para que el semáforo se refresque', function () {
    entrarParaImprevistos(Rol::Administrador);
    $lote = loteParaImprevistos();
    Event::fake([LoteActualizado::class]);

    $id = postJson(rutaDeImprevistos($lote, paradaParaImprevistos($lote, 'FUS')), cuerpoDeImprevisto())
        ->assertCreated()
        ->json('id');

    Event::assertDispatchedTimes(LoteActualizado::class, 1);

    patchJson("/api/lotes/{$lote->codigo}/imprevistos/{$id}", ['resuelto' => true])->assertOk();

    Event::assertDispatchedTimes(LoteActualizado::class, 2);
    Event::assertDispatched(LoteActualizado::class, fn (LoteActualizado $evento) => $evento->lote->is($lote));
});

it('avisa a los administradores activos al registrar un imprevisto', function () {
    Notification::fake();
    $activo = entrarParaImprevistos(Rol::Administrador);
    $inactivo = User::factory()->create(['activo' => false]);
    $inactivo->assignRole(Rol::Administrador);
    $ventas = User::factory()->create();
    $ventas->assignRole(Rol::Ventas);
    $lote = loteParaImprevistos();

    postJson(rutaDeImprevistos($lote, paradaParaImprevistos($lote, 'FUS')), cuerpoDeImprevisto())
        ->assertCreated();

    Notification::assertSentTo($activo, NotificacionImprevisto::class);
    Notification::assertNotSentTo([$inactivo, $ventas], NotificacionImprevisto::class);
});

it('no avisa cuando el imprevisto se registra ya resuelto', function () {
    Notification::fake();
    entrarParaImprevistos(Rol::Administrador);
    $lote = loteParaImprevistos();

    postJson(rutaDeImprevistos($lote, paradaParaImprevistos($lote, 'FUS')), cuerpoDeImprevisto(['resuelto' => true]))
        ->assertCreated();

    Notification::assertNothingSent();
});

it('no llega a Mongo real en los tests', function () {
    expect(app(AlmacenImprevistos::class))->toBeInstanceOf(AlmacenImprevistosFake::class);
});
