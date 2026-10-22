<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        JsonResource::withoutWrapping();

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
    }
}
