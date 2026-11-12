<?php

use App\Http\Controllers\AjusteInsumoController;
use App\Http\Controllers\AjusteLoteController;
use App\Http\Controllers\AreaController;
use App\Http\Controllers\CierreLoteController;
use App\Http\Controllers\EntradaInsumoController;
use App\Http\Controllers\InsumoController;
use App\Http\Controllers\InventarioController;
use App\Http\Controllers\LoteController;
use App\Http\Controllers\MovimientoInsumoController;
use App\Http\Controllers\ParadaLoteController;
use App\Http\Controllers\PreguntaSecretaController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\UsuarioController;
use App\Http\Resources\UsuarioResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Laravel\Pennant\Middleware\EnsureFeaturesAreActive;

Route::middleware(['auth:sanctum', 'activo'])->group(function () {
    Route::get('/user', fn (Request $request) => new UsuarioResource($request->user()?->load('areas')));

    Route::get('/perfil/pregunta-secreta', [PreguntaSecretaController::class, 'mostrar']);
    Route::put('/perfil/pregunta-secreta', [PreguntaSecretaController::class, 'guardar']);

    Route::middleware('role:administrador')->group(function () {
        Route::apiResource('insumos', InsumoController::class);
    });

    Route::middleware('role:administrador')->group(function () {
        Route::get('areas/opciones', [AreaController::class, 'opciones']);
        Route::apiResource('areas', AreaController::class);
        Route::post('/insumos/{insumo}/ajustes', [AjusteInsumoController::class, 'store']);
    });

    Route::middleware('role:administrador')->group(function () {
        Route::apiResource('usuarios', UsuarioController::class)
            ->only(['index', 'store', 'update', 'destroy']);
        Route::post('/insumos/{insumo}/entradas', [EntradaInsumoController::class, 'store']);
        Route::get('/insumos/{insumo}/movimientos', [MovimientoInsumoController::class, 'index']);
    });

    Route::middleware('role:administrador|ventas')->group(function () {
        Route::get('/inventario/insumos', [InventarioController::class, 'insumos']);
        Route::get('/inventario/productos', [InventarioController::class, 'productos']);
    });

    Route::middleware('role:administrador')->group(function () {
        Route::get('productos/opciones', [ProductoController::class, 'opciones']);
        Route::apiResource('productos', ProductoController::class);
    });

    Route::middleware('role:administrador|ventas|encargado')->group(function () {
        Route::get('/lotes', [LoteController::class, 'index']);
        Route::get('/lotes/{lote}', [LoteController::class, 'show']);
    });

    Route::middleware('role:administrador')->group(function () {
        Route::post('/lotes', [LoteController::class, 'store']);
        Route::post('/lotes/{lote}/confirmar', [CierreLoteController::class, 'confirmar']);
        Route::post('/lotes/{lote}/cancelar', [CierreLoteController::class, 'cancelar']);
        Route::get('/lotes/{lote}/ajustes', [AjusteLoteController::class, 'index']);
        Route::post('/lotes/{lote}/ajustes', [AjusteLoteController::class, 'store']);
    });

    Route::middleware('role:administrador|encargado')->scopeBindings()->group(function () {
        Route::post('/lotes/{lote}/paradas/{parada}/entrada', [ParadaLoteController::class, 'entrada']);
        Route::post('/lotes/{lote}/paradas/{parada}/salida', [ParadaLoteController::class, 'salida']);
    });

    // Módulo de ventas (apagado por defecto con Pennant). Las rutas de ventas
    // —clientes, pedidos, reportes, cotizaciones— van dentro de este grupo, así
    // nacen protegidas: con la bandera apagada no responden aunque se llamen.
    Route::middleware(EnsureFeaturesAreActive::using('ventas'))->group(function () {
        // Aún no hay endpoints de ventas; llegan en sus tareas.
    });
});
