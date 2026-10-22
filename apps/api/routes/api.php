<?php

use App\Http\Resources\UsuarioResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'activo'])->group(function () {
    Route::get('/user', fn (Request $request) => new UsuarioResource($request->user()));
});
