<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        JsonResource::withoutWrapping();

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
    }
}
