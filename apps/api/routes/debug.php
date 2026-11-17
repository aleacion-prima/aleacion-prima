<?php

use App\Http\Controllers\DebugController;
use Illuminate\Support\Facades\Route;

Route::get('/usuarios', [DebugController::class, 'usuarios']);
Route::post('/entrar-como/{user}', [DebugController::class, 'entrarComo']);
