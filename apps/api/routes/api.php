<?php

use App\Http\Controllers\AjusteInsumoController;
use App\Http\Controllers\AreaController;
use App\Http\Controllers\EntradaInsumoController;
use App\Http\Controllers\InsumoController;
use App\Http\Controllers\InventarioController;
use App\Http\Controllers\MovimientoInsumoController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\UsuarioController;
use App\Http\Resources\UsuarioResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'activo'])->group(function () {
    Route::get('/user', fn (Request $request) => new UsuarioResource($request->user()));

    Route::middleware('role:administrador')->group(function () {
        Route::apiResource('insumos', InsumoController::class);
    });

    Route::middleware('role:administrador')->group(function () {
        Route::apiResource('areas', AreaController::class);
        Route::post('/insumos/{insumo}/ajustes', [AjusteInsumoController::class, 'store']);
    });

    Route::middleware('role:administrador')->group(function () {
        Route::get('/areas-disponibles', [UsuarioController::class, 'areas']);
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
});
