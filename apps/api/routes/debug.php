<?php

use App\Http\Controllers\DebugController;
use Illuminate\Support\Facades\Route;

Route::get('/usuarios', [DebugController::class, 'usuarios']);
Route::post('/entrar-como/{user}', [DebugController::class, 'entrarComo']);
Route::post('/lotes', [DebugController::class, 'crearLote']);
Route::post('/lotes/varios', [DebugController::class, 'crearVarios']);
Route::post('/lotes/{lote}/avanzar', [DebugController::class, 'avanzar']);
Route::post('/inventario/rellenar', [DebugController::class, 'rellenarInventario']);
Route::post('/areas/alternar', [DebugController::class, 'alternarAreas']);
Route::post('/reiniciar', [DebugController::class, 'reiniciar']);
