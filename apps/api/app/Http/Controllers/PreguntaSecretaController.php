<?php

namespace App\Http\Controllers;

use App\Http\Requests\GuardarPreguntaSecretaRequest;
use App\Http\Requests\RecuperarPreguntaRequest;
use App\Http\Requests\RestablecerPorPreguntaRequest;
use App\Models\User;
use App\Recuperacion\RespuestaSecreta;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PreguntaSecretaController extends Controller
{
    /**
     * Panel (autenticado): la pregunta actual del usuario y la lista disponible.
     */
    public function mostrar(Request $request): JsonResponse
    {
        $usuario = $request->user();
        abort_unless($usuario instanceof User, 401);

        return response()->json([
            'pregunta' => $usuario->pregunta_secreta,
            'preguntas' => array_values((array) config('preguntas-secretas.preguntas')),
        ]);
    }

    /**
     * Panel (autenticado): el usuario elige o cambia su pregunta y respuesta.
     * La respuesta se guarda hasheada y normalizada, nunca en texto plano.
     */
    public function guardar(GuardarPreguntaSecretaRequest $request): JsonResponse
    {
        $usuario = $request->user();
        abort_unless($usuario instanceof User, 401);

        $usuario->forceFill([
            'pregunta_secreta' => $request->string('pregunta')->toString(),
            'respuesta_secreta' => Hash::make(
                RespuestaSecreta::normalizar($request->string('respuesta')->toString()),
            ),
        ])->save();

        return response()->json(['message' => 'Tu pregunta de seguridad quedó guardada.']);
    }

    /**
     * Guest: dado el correo, devuelve la pregunta del usuario (si tiene una).
     * Mostrar la pregunta revela que la cuenta existe; es el diseño de este método.
     */
    public function pregunta(RecuperarPreguntaRequest $request): JsonResponse
    {
        $usuario = User::query()->where('email', $request->string('email')->toString())->first();

        if (! $usuario || ! $usuario->pregunta_secreta) {
            throw ValidationException::withMessages([
                'email' => ['No hay una pregunta de seguridad para ese correo.'],
            ]);
        }

        return response()->json(['pregunta' => $usuario->pregunta_secreta]);
    }

    /**
     * Guest: verifica la respuesta y, si es correcta, cambia la contraseña. El
     * límite de intentos lo aplica el middleware throttle:pregunta-secreta.
     */
    public function restablecer(RestablecerPorPreguntaRequest $request): JsonResponse
    {
        $usuario = User::query()->where('email', $request->string('email')->toString())->first();

        $respuestaCorrecta = $usuario
            && $usuario->respuesta_secreta
            && Hash::check(
                RespuestaSecreta::normalizar($request->string('respuesta')->toString()),
                $usuario->respuesta_secreta,
            );

        if (! $usuario || ! $respuestaCorrecta) {
            throw ValidationException::withMessages([
                'respuesta' => ['La respuesta no es correcta.'],
            ]);
        }

        $usuario->forceFill(['password' => $request->string('password')->toString()])
            ->setRememberToken(Str::random(60));
        $usuario->save();

        event(new PasswordReset($usuario));

        return response()->json(['message' => __('passwords.reset')]);
    }
}
