<?php

namespace App\Http\Controllers;

use App\Enums\Rol;
use App\Http\Requests\LoginRequest;
use App\Http\Resources\UsuarioResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

class SesionMovilController extends Controller
{
    public function iniciar(LoginRequest $request): JsonResponse
    {
        $usuario = User::query()
            ->where('email', $request->string('email')->toString())
            ->where('activo', true)
            ->first();

        if (! $usuario || ! Hash::check($request->string('password')->toString(), $usuario->password)) {
            throw ValidationException::withMessages(['credenciales' => __('auth.failed')]);
        }

        abort_unless($usuario->hasAnyRole([Rol::Administrador, Rol::Encargado]), 403, __('auth.sin_app'));

        return response()->json([
            'token' => $usuario->createToken('movil', ['lotes:read'])->plainTextToken,
            'usuario' => new UsuarioResource($usuario->load('areas')),
        ], 201);
    }

    public function cerrar(Request $request): Response
    {
        $token = $request->user()?->currentAccessToken();

        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }

        return response()->noContent();
    }
}
