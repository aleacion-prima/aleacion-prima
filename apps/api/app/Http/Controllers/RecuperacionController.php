<?php

namespace App\Http\Controllers;

use App\Http\Requests\EnviarEnlaceRequest;
use App\Http\Requests\RestablecerContrasenaRequest;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RecuperacionController extends Controller
{
    /**
     * Envía el enlace para restablecer la contraseña. Responde siempre lo mismo,
     * exista o no el correo, para no revelar quién tiene cuenta.
     */
    public function enviarEnlace(EnviarEnlaceRequest $request): JsonResponse
    {
        Password::sendResetLink($request->only('email'));

        return response()->json(['message' => __('passwords.sent')]);
    }

    /**
     * Restablece la contraseña con el token del correo. El token caduca (config
     * auth.passwords.users.expire) y solo sirve una vez (se borra al usarlo).
     * Al cambiar la contraseña, AuthenticateSession (Sanctum) cierra las demás
     * sesiones en su siguiente petición.
     */
    public function restablecer(RestablecerContrasenaRequest $request): JsonResponse
    {
        $estado = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $usuario, string $password): void {
                $usuario->forceFill(['password' => $password])
                    ->setRememberToken(Str::random(60));
                $usuario->save();

                event(new PasswordReset($usuario));
            },
        );

        if ($estado === Password::PASSWORD_RESET) {
            return response()->json(['message' => __('passwords.reset')]);
        }

        // Token inválido o caducado, o correo no encontrado.
        throw ValidationException::withMessages(['email' => [__($estado)]]);
    }
}
