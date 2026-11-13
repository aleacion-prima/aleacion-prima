<?php

namespace App\Http\Controllers;

use App\Acceso\SegundoFactor;
use App\Http\Requests\LoginRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class SesionController extends Controller
{
    public function iniciar(LoginRequest $request, SegundoFactor $segundoFactor): JsonResponse|Response
    {
        $usuario = $request->usuarioAutenticado();

        // Con segundo factor: no se inicia la sesión todavía. Se deja pendiente
        // de verificar y se envía el código; ninguna ruta protegida responde
        // hasta mandar el código a /login/verificar.
        if ($usuario->segundo_factor) {
            $request->session()->put('acceso.pendiente', $usuario->id);
            $request->session()->put('acceso.recordar', $request->boolean('recordar'));
            $segundoFactor->enviar($usuario);

            return response()->json(['segundo_factor' => true]);
        }

        Auth::login($usuario, $request->boolean('recordar'));
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
