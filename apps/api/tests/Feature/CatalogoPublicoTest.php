<?php

use App\Events\DisponibilidadActualizada;
use App\Models\Producto;
use Illuminate\Routing\Route as Ruta;
use Illuminate\Support\Facades\Route;

use function Pest\Laravel\getJson;

function claves(mixed $datos): array
{
    if (! is_array($datos)) {
        return [];
    }

    return collect($datos)
        ->flatMap(fn ($valor, $clave) => [(string) $clave, ...claves($valor)])
        ->all();
}

function sinDatosPrivados(array $datos, Producto $producto): void
{
    expect(collect(claves($datos))->filter(fn (string $clave) => preg_match('/precio|costo|tarifa|insumo|existencia/i', $clave))->all())
        ->toBeEmpty()
        ->and(json_encode($datos))->not->toContain((string) $producto->precio_mayoreo)
        ->and(json_encode($datos))->not->toContain((string) $producto->precio_menudeo);
}

it('lista solo los productos publicados y activos, sin sesión', function () {
    $publicado = Producto::factory()->create(['publicado' => true, 'existencia' => 7]);
    Producto::factory()->create(['publicado' => false]);
    Producto::factory()->inactivo()->create(['publicado' => true]);

    getJson('/api/publico/productos')
        ->assertOk()
        ->assertJsonCount(1)
        ->assertExactJson([[
            'id' => $publicado->id,
            'nombre' => $publicado->nombre,
            'especificaciones' => $publicado->especificaciones,
            'disponible' => 7,
        ]]);
});

it('muestra el detalle de un producto publicado y oculta los demás', function () {
    $publicado = Producto::factory()->create(['publicado' => true]);
    $oculto = Producto::factory()->create(['publicado' => false]);

    getJson("/api/publico/productos/{$publicado->id}")->assertOk()->assertJsonPath('id', $publicado->id);
    getJson("/api/publico/productos/{$oculto->id}")->assertNotFound();
});

it('ninguna ruta pública ni el evento del catálogo incluyen precios, costos ni existencias de insumos', function () {
    $producto = Producto::factory()->create(['publicado' => true, 'precio_mayoreo' => 321.45, 'precio_menudeo' => 543.21]);

    $rutas = collect(Route::getRoutes()->getRoutes())
        ->filter(fn (Ruta $ruta) => str_starts_with($ruta->uri(), 'api/publico') && in_array('GET', $ruta->methods(), true));

    expect($rutas)->not->toBeEmpty();

    $rutas->each(function (Ruta $ruta) use ($producto) {
        $respuesta = getJson('/'.str_replace('{producto}', (string) $producto->id, $ruta->uri()))->assertOk();
        sinDatosPrivados($respuesta->json(), $producto);
    });

    sinDatosPrivados((new DisponibilidadActualizada($producto))->broadcastWith(), $producto);
});
