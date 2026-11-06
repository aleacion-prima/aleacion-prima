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
     * Guest: dado el correo, devuelve la pregunta del usuario. Si el correo no
     * existe o no tiene pregunta, devuelve una pregunta falsa pero estable, para
     * no revelar si la cuenta existe.
     */
    public function pregunta(RecuperarPreguntaRequest $request): JsonResponse
    {
        $correo = $request->string('email')->toString();
        $usuario = User::query()->where('email', $correo)->first();

        // Si no hay usuario o no tiene pregunta, se devuelve una pregunta falsa
        // pero estable para ese correo, para no revelar si la cuenta existe.
        if (! $usuario || ! $usuario->pregunta_secreta) {
            return response()->json(['pregunta' => $this->preguntaFalsa($correo)]);
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

        // Al cambiar la contraseña, se revocan los tokens de la app móvil.
        $usuario->tokens()->delete();

        event(new PasswordReset($usuario));

        return response()->json(['message' => __('passwords.reset')]);
    }

    /**
     * Pregunta estable (siempre la misma para un mismo correo) para los correos
     * sin pregunta o inexistentes, de modo que la respuesta no revele si la
     * cuenta existe.
     */
    private function preguntaFalsa(string $correo): string
    {
        $preguntas = array_values((array) config('preguntas-secretas.preguntas'));
        $indice = (int) (crc32(mb_strtolower(trim($correo))) % count($preguntas));

        return (string) $preguntas[$indice];
    }
}
