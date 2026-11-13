<?php

use App\Http\Controllers\PreguntaSecretaController;
use App\Http\Controllers\RecuperacionController;
use App\Http\Controllers\SegundoFactorController;
use App\Http\Controllers\SesionController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::post('/login', [SesionController::class, 'iniciar'])->middleware(['throttle:login']);
Route::post('/logout', [SesionController::class, 'cerrar'])->middleware('auth');

// Segundo factor: la sesión queda pendiente tras el login y entra con el código.
Route::post('/login/verificar', [SegundoFactorController::class, 'verificar'])
    ->middleware(['guest', 'throttle:verificar']);
Route::post('/login/reenviar', [SegundoFactorController::class, 'reenviar'])
    ->middleware(['guest', 'throttle:reenviar']);

Route::post('/forgot-password', [RecuperacionController::class, 'enviarEnlace'])
    ->middleware(['guest', 'throttle:recuperacion']);
Route::post('/reset-password', [RecuperacionController::class, 'restablecer'])
    ->middleware(['guest', 'throttle:recuperacion']);

Route::post('/forgot-password/pregunta', [PreguntaSecretaController::class, 'pregunta'])
    ->middleware(['guest', 'throttle:recuperacion']);
Route::post('/reset-password/pregunta', [PreguntaSecretaController::class, 'restablecer'])
    ->middleware(['guest', 'throttle:pregunta-secreta']);
