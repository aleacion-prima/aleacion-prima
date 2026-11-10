<?php

namespace App\Providers;

use App\Events\LoteActualizado;
use App\Listeners\DifundirModulos;
use App\Lotes\RecalcularCostoAlActualizar;
use App\Models\User;
use App\Support\Modulos;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Laravel\Pennant\Events\FeatureUpdated;
use Laravel\Pennant\Events\FeatureUpdatedForAllScopes;
use Laravel\Pennant\Feature;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        JsonResource::withoutWrapping();

        // Cada vez que un lote cambia, se recalcula su costo.
        Event::listen(LoteActualizado::class, RecalcularCostoAlActualizar::class);

        // Política de contraseñas del proyecto: mínimo 8, letras, mayúsculas y
        // minúsculas, y números. Se usa en la recuperación y en el alta/edición
        // de usuarios. El máximo de 72 (límite de bcrypt) se agrega como regla
        // 'max:72' junto a Password::defaults() en cada Form Request.
        Password::defaults(fn () => Password::min(8)->letters()->mixedCase()->numbers());

        // El enlace del correo de recuperación apunta al panel (SPA), no a la API.
        ResetPassword::createUrlUsing(function (User $usuario, string $token): string {
            $base = rtrim((string) config('panel.url'), '/');
            $correo = urlencode($usuario->getEmailForPasswordReset());

            return "{$base}/nueva-contrasena?token={$token}&email={$correo}";
        });

        RateLimiter::for('login', function (Request $request) {
            $correo = mb_strtolower($request->string('email')->toString());

            return Limit::perMinute(5)
                ->by($correo.'|'.$request->ip())
                ->response(fn (Request $request, array $headers) => response()->json(
                    ['message' => __('auth.throttle', ['seconds' => $headers['Retry-After'] ?? 60])],
                    429,
                    $headers,
                ));
        });

        // Bloqueo por cuenta: 5 intentos fallidos y 15 minutos bloqueada, contando
        // solo por correo (no por IP), para frenar el adivinado de la respuesta.
        RateLimiter::for('pregunta-secreta', function (Request $request) {
            $correo = mb_strtolower($request->string('email')->toString());

            return Limit::perMinutes(15, 5)
                ->by($correo)
                ->response(fn (Request $request, array $headers) => response()->json(
                    ['message' => __('auth.throttle', ['seconds' => $headers['Retry-After'] ?? 60])],
                    429,
                    $headers,
                ));
        });

        RateLimiter::for('recuperacion', function (Request $request) {
            $correo = mb_strtolower($request->string('email')->toString());

            return Limit::perMinute(5)
                ->by($correo.'|'.$request->ip())
                ->response(fn (Request $request, array $headers) => response()->json(
                    ['message' => __('auth.throttle', ['seconds' => $headers['Retry-After'] ?? 60])],
                    429,
                    $headers,
                ));
        });

        // --- Módulos (feature flags con Pennant) ---
        // Scope global: una bandera vale igual para todos, no por usuario.
        Feature::resolveScopeUsing(fn () => null);

        // Cada módulo arranca apagado.
        foreach (Modulos::nombres() as $modulo) {
            Feature::define($modulo, fn () => false);
        }

        // Al cambiar una bandera, difundir el estado de los módulos por Reverb.
        Event::listen(FeatureUpdated::class, DifundirModulos::class);
        Event::listen(FeatureUpdatedForAllScopes::class, DifundirModulos::class);
    }
}
