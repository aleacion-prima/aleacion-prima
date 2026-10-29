<?php

use App\Http\Controllers\AreaController;
use App\Http\Resources\UsuarioResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'activo'])->group(function () {
    Route::get('/user', fn (Request $request) => new UsuarioResource($request->user()));

    Route::middleware('role:administrador')->group(function () {
        Route::apiResource('areas', AreaController::class);
    });
});
