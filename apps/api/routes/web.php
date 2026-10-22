<?php

use App\Http\Controllers\SesionController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::post('/login', [SesionController::class, 'iniciar'])->middleware(['guest', 'throttle:login']);
Route::post('/logout', [SesionController::class, 'cerrar'])->middleware('auth');
