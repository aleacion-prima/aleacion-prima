<?php

use App\Http\Controllers\RecuperacionController;
use App\Http\Controllers\SesionController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::post('/login', [SesionController::class, 'iniciar'])->middleware(['guest', 'throttle:login']);
Route::post('/logout', [SesionController::class, 'cerrar'])->middleware('auth');

Route::post('/forgot-password', [RecuperacionController::class, 'enviarEnlace'])
    ->middleware(['guest', 'throttle:recuperacion']);
Route::post('/reset-password', [RecuperacionController::class, 'restablecer'])
    ->middleware(['guest', 'throttle:recuperacion']);
