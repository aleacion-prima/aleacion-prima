<?php

namespace App\Http\Controllers;

use App\Acceso\SegundoFactor;
use App\Http\Requests\ActualizarSegundoFactorRequest;
use App\Http\Requests\VerificarCodigoRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class SegundoFactorController extends Controller
{
    /**
     * Challenge: con la sesión pendiente (user_id guardado por el login),
     * comprueba el código y recién entonces inicia la sesión.
     */
    public function verificar(VerificarCodigoRequest $request, SegundoFactor $segundoFactor): Response
    {
        $usuario = $this->pendiente($request);

        $segundoFactor->comprobar($usuario, $request->string('codigo')->toString());

        Auth::login($usuario, (bool) $request->session()->get('acceso.recordar', false));
        $request->session()->forget(['acceso.pendiente', 'acceso.recordar']);
        $request->session()->regenerate();

        return response()->noContent();
    }

    /**
     * Reenvía un código nuevo a la sesión pendiente. La frecuencia la limita el
     * middleware throttle:reenviar.
     */
    public function reenviar(Request $request, SegundoFactor $segundoFactor): JsonResponse
    {
        $usuario = $this->pendiente($request);

        $segundoFactor->enviar($usuario);

        return response()->json(['message' => 'Te enviamos un código nuevo.']);
    }

    /**
     * Mi cuenta: estado del segundo factor del usuario en sesión.
     */
    public function mostrar(Request $request, SegundoFactor $segundoFactor): JsonResponse
    {
        $usuario = $request->user();
        abort_unless($usuario instanceof User, 401);

        return response()->json([
            'activo' => $segundoFactor->activoPara($usuario),
            'obligatorio' => $segundoFactor->obligatorioPara($usuario),
        ]);
    }

    /**
     * Mi cuenta: el usuario activa o desactiva su segundo factor. El
     * administrador no puede desactivarlo (es obligatorio para su rol).
     */
    public function actualizar(ActualizarSegundoFactorRequest $request, SegundoFactor $segundoFactor): JsonResponse
    {
        $usuario = $request->user();
        abort_unless($usuario instanceof User, 401);

        $activar = $request->boolean('activo');

        if (! $activar && $segundoFactor->obligatorioPara($usuario)) {
            throw ValidationException::withMessages([
                'activo' => ['El administrador no puede desactivar su segundo factor.'],
            ]);
        }

        $usuario->forceFill(['segundo_factor' => $activar])->save();

        return response()->json([
            'activo' => $segundoFactor->activoPara($usuario),
            'message' => $activar ? 'Segundo factor activado.' : 'Segundo factor desactivado.',
        ]);
    }

    /**
     * Usuario activo detrás de la sesión pendiente de verificar, o 422 si ya no
     * la hay (la sesión expiró o nunca pasó por el login).
     */
    private function pendiente(Request $request): User
    {
        $id = (int) $request->session()->get('acceso.pendiente', 0);
        $usuario = $id > 0 ? User::query()->where('activo', true)->find($id) : null;

        if (! $usuario instanceof User) {
            throw ValidationException::withMessages([
                'codigo' => ['Tu sesión expiró. Inicia sesión de nuevo.'],
            ]);
        }

        return $usuario;
    }
}
