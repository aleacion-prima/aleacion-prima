<?php

use App\Http\Controllers\UsuarioController;
use App\Http\Resources\UsuarioResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'activo'])->group(function () {
    Route::get('/user', fn (Request $request) => new UsuarioResource($request->user()));

    Route::middleware('role:administrador')->group(function () {
        Route::get('/areas-disponibles', [UsuarioController::class, 'areas']);
        Route::apiResource('usuarios', UsuarioController::class)
            ->only(['index', 'store', 'update', 'destroy']);
    });
});
