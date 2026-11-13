<?php

namespace App\Acceso;

use App\Models\CodigoAcceso;
use App\Models\User;
use App\Notifications\CodigoAccesoCorreo;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Genera, envía y comprueba el código del segundo factor. Es el único lugar que
 * escribe la tabla `codigos_acceso`.
 */
class SegundoFactor
{
    /**
     * Genera un código nuevo de 6 dígitos, lo guarda hasheado (reemplazando el
     * anterior del usuario, con los intentos en cero) y lo envía por correo.
     */
    public function enviar(User $usuario): void
    {
        $codigo = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        CodigoAcceso::query()->updateOrCreate(
            ['user_id' => $usuario->id],
            [
                'codigo' => Hash::make($codigo),
                'expira_en' => now()->addMinutes($this->vigencia()),
                'intentos' => 0,
            ],
        );

        $usuario->notify(new CodigoAccesoCorreo($codigo));
    }

    /**
     * Comprueba el código. Lanza ValidationException (422) si venció, si ya no
     * quedan intentos o si no coincide. Si es correcto, borra el registro (un
     * solo uso).
     */
    public function comprobar(User $usuario, string $codigo): void
    {
        $registro = CodigoAcceso::query()->where('user_id', $usuario->id)->first();

        if (! $registro instanceof CodigoAcceso || $registro->vencido()) {
            $registro?->delete();

            throw ValidationException::withMessages([
                'codigo' => ['El código venció o ya no es válido. Pide uno nuevo.'],
            ]);
        }

        if ($registro->intentos >= $this->maxIntentos()) {
            $registro->delete();

            throw ValidationException::withMessages([
                'codigo' => ['Agotaste los intentos. Pide un código nuevo.'],
            ]);
        }

        if (! Hash::check($codigo, $registro->codigo)) {
            $registro->increment('intentos');

            throw ValidationException::withMessages([
                'codigo' => ['El código no es correcto.'],
            ]);
        }

        $registro->delete();
    }

    private function vigencia(): int
    {
        return (int) config('segundo-factor.vigencia_minutos', 10);
    }

    private function maxIntentos(): int
    {
        return (int) config('segundo-factor.max_intentos', 5);
    }
}
