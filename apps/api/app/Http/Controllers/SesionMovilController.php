<?php

namespace App\Http\Controllers;

use App\Enums\Rol;
use App\Http\Requests\LoginRequest;
use App\Http\Resources\UsuarioResource;
use App\Models\User;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
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
            event(new Failed('web', $usuario, [
                'email' => $request->string('email')->toString(),
                'password' => $request->string('password')->toString(),
            ]));

            throw ValidationException::withMessages(['credenciales' => __('auth.failed')]);
        }

        abort_unless($usuario->hasAnyRole([Rol::Administrador, Rol::Encargado]), 403, __('auth.sin_app'));

        $token = $usuario->createToken('movil', ['lotes:read'])->plainTextToken;
        event(new Login('web', $usuario, false));

        return response()->json([
            'token' => $token,
            'usuario' => new UsuarioResource($usuario->load('areas')),
        ], 201);
    }

    public function cerrar(Request $request): Response
    {
        $usuario = $request->user();
        $token = $usuario?->currentAccessToken();

        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }

        if ($usuario instanceof User) {
            event(new Logout('web', $usuario));
        }

        return response()->noContent();
    }
}
