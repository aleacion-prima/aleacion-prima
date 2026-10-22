<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class SesionController extends Controller
{
    public function iniciar(LoginRequest $request): Response
    {
        $request->autenticar();
        $request->session()->regenerate();

        return response()->noContent();
    }

    public function cerrar(Request $request): Response
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->noContent();
    }
}
