<?php

use App\Http\Controllers\AjusteInsumoController;
use App\Http\Controllers\AreaController;
use App\Http\Controllers\BitacoraLoteController;
use App\Http\Controllers\CierreLoteController;
use App\Http\Controllers\CostoLoteController;
use App\Http\Controllers\EntradaInsumoController;
use App\Http\Controllers\HistorialLoteController;
use App\Http\Controllers\ImprevistoLoteController;
use App\Http\Controllers\InsumoController;
use App\Http\Controllers\InventarioController;
use App\Http\Controllers\LoteController;
use App\Http\Controllers\MovimientoInsumoController;
use App\Http\Controllers\NotificacionController;
use App\Http\Controllers\ParadaLoteController;
use App\Http\Controllers\PreguntaSecretaController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\SegundoFactorController;
use App\Http\Controllers\SesionActivaController;
use App\Http\Controllers\SesionMovilController;
use App\Http\Controllers\TableroController;
use App\Http\Controllers\UsuarioController;
use App\Http\Resources\SesionResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Laravel\Pennant\Middleware\EnsureFeaturesAreActive;

Route::post('/movil/login', [SesionMovilController::class, 'iniciar'])->middleware('throttle:login');

Route::middleware(['auth:sanctum', 'activo'])->group(function () {
    Route::get('/user', fn (Request $request) => new SesionResource($request->user()?->load('areas')));
    Route::post('/logout', [SesionMovilController::class, 'cerrar']);

    Route::middleware(['role:administrador|ventas|encargado', 'ability:lotes:read'])->group(function () {
        Route::get('/lotes', [LoteController::class, 'index']);
        Route::get('/lotes/{lote}', [LoteController::class, 'show']);
        Route::get('/lotes/{lote}/imprevistos', [ImprevistoLoteController::class, 'index']);
        Route::get('/lotes/{lote}/historial', HistorialLoteController::class);
    });

    Route::middleware('ability:panel')->group(function () {
        Route::get('/perfil/pregunta-secreta', [PreguntaSecretaController::class, 'mostrar']);
        Route::put('/perfil/pregunta-secreta', [PreguntaSecretaController::class, 'guardar']);

        Route::get('/perfil/segundo-factor', [SegundoFactorController::class, 'mostrar']);
        Route::put('/perfil/segundo-factor', [SegundoFactorController::class, 'actualizar']);

        // Sesiones activas del propio usuario (panel + tokens de la app).
        Route::get('/sesiones', [SesionActivaController::class, 'index']);
        Route::delete('/sesiones', [SesionActivaController::class, 'destroyOtras']);
        Route::delete('/sesiones/{id}', [SesionActivaController::class, 'destroy'])
            ->where('id', '[a-z]+:[A-Za-z0-9]+');

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

            // Admin: ver y cerrar las sesiones de otro usuario desde Usuarios.
            Route::get('/usuarios/{usuario}/sesiones', [SesionActivaController::class, 'indexDe']);
            Route::delete('/usuarios/{usuario}/sesiones', [SesionActivaController::class, 'destroyTodasDe']);
            Route::delete('/usuarios/{usuario}/sesiones/{id}', [SesionActivaController::class, 'destroyDe'])
                ->where('id', '[a-z]+:[A-Za-z0-9]+');

            Route::post('/insumos/{insumo}/entradas', [EntradaInsumoController::class, 'store']);
            Route::get('/insumos/{insumo}/movimientos', [MovimientoInsumoController::class, 'index']);
        });

        Route::middleware('role:administrador|ventas')->group(function () {
            Route::get('/inventario/insumos', [InventarioController::class, 'insumos']);
            Route::get('/inventario/productos', [InventarioController::class, 'productos']);
            Route::get('/lotes/{lote}/bitacora', BitacoraLoteController::class);
            Route::get('/lotes/{lote}/costo', CostoLoteController::class);
        });

        Route::middleware('role:administrador')->group(function () {
            Route::get('/notificaciones', [NotificacionController::class, 'index']);
            Route::patch('/notificaciones/{id}/leida', [NotificacionController::class, 'leida'])
                ->whereUuid('id');
            Route::post('/notificaciones/leidas', [NotificacionController::class, 'leidas']);
        });

        Route::middleware('role:administrador')->group(function () {
            Route::get('productos/opciones', [ProductoController::class, 'opciones']);
            Route::apiResource('productos', ProductoController::class);
        });

        Route::middleware('role:administrador')->group(function () {
            Route::post('/lotes', [LoteController::class, 'store']);
            Route::post('/lotes/{lote}/confirmar', [CierreLoteController::class, 'confirmar']);
            Route::post('/lotes/{lote}/cancelar', [CierreLoteController::class, 'cancelar']);
            Route::get('/tablero', TableroController::class);
        });

        Route::middleware('role:administrador|encargado')->scopeBindings()->group(function () {
            Route::post('/lotes/{lote}/paradas/{parada}/entrada', [ParadaLoteController::class, 'entrada']);
            Route::post('/lotes/{lote}/paradas/{parada}/salida', [ParadaLoteController::class, 'salida']);

            Route::post('/lotes/{lote}/paradas/{parada}/imprevistos', [ImprevistoLoteController::class, 'store']);
            Route::patch('/lotes/{lote}/imprevistos/{imprevisto}', [ImprevistoLoteController::class, 'update']);
        });

        // Módulo de ventas (apagado por defecto con Pennant). Las rutas de ventas
        // —clientes, pedidos, reportes, cotizaciones— van dentro de este grupo, así
        // nacen protegidas: con la bandera apagada no responden aunque se llamen.
        Route::middleware(EnsureFeaturesAreActive::using('ventas'))->group(function () {
            // Aún no hay endpoints de ventas; llegan en sus tareas.
        });
    });
});
