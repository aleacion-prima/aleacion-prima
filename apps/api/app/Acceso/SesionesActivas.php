<?php

namespace App\Acceso;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Consulta y revoca las sesiones del panel (tabla `sessions`, driver database) y
 * los tokens de la app (`personal_access_tokens`) de un usuario. Es el único lugar
 * que decide, a partir del id compuesto `tipo:id`, de qué tabla borrar.
 */
class SesionesActivas
{
    /**
     * Lista las sesiones del panel y los tokens de la app del usuario con una
     * forma uniforme, ordenadas por última actividad (la actual primero).
     *
     * @return Collection<int, array{id: string, tipo: string, dispositivo: string, ip: string|null, ultima_actividad: string|null, actual: bool}>
     */
    public function listar(User $usuario, ?string $sesionActualId, ?string $tokenActualId): Collection
    {
        $sesiones = DB::table('sessions')
            ->where('user_id', $usuario->id)
            ->get()
            ->map(fn (\stdClass $fila): array => [
                'id' => "panel:{$fila->id}",
                'tipo' => 'panel',
                'dispositivo' => $this->dispositivoDesdeUserAgent($fila->user_agent === null ? null : (string) $fila->user_agent),
                'ip' => $fila->ip_address === null ? null : (string) $fila->ip_address,
                'ultima_actividad' => Carbon::createFromTimestamp($fila->last_activity)->toISOString(),
                'actual' => $fila->id === $sesionActualId,
            ]);

        $tokens = $usuario->tokens
            ->map(fn (PersonalAccessToken $token): array => [
                'id' => "app:{$token->id}",
                'tipo' => 'app',
                'dispositivo' => $token->name === 'movil' ? 'App Android' : $token->name,
                'ip' => null,
                'ultima_actividad' => ($token->last_used_at ?? $token->created_at)?->toISOString(),
                'actual' => (string) $token->id === $tokenActualId,
            ]);

        return $sesiones
            ->concat($tokens)
            ->sortByDesc(fn (array $sesion): array => [
                $sesion['actual'] ? 1 : 0,
                $sesion['ultima_actividad'] ?? '',
            ])
            ->values();
    }

    /**
     * Revoca una sesión del panel o un token de la app a partir del id compuesto
     * `tipo:id`. Devuelve si borró algo (el controlador aborta 404 si no).
     */
    public function revocar(User $usuario, string $idCompuesto): bool
    {
        $partes = explode(':', $idCompuesto, 2);

        if (count($partes) !== 2) {
            return false;
        }

        [$tipo, $id] = $partes;

        return match ($tipo) {
            'panel' => DB::table('sessions')
                ->where('id', $id)
                ->where('user_id', $usuario->id)
                ->delete() > 0,
            'app' => $usuario->tokens()->whereKey($id)->delete() > 0,
            default => false,
        };
    }

    /**
     * Cierra todas las sesiones del panel salvo `exceptoSesionId` y todos los
     * tokens de la app del usuario.
     */
    public function revocarTodas(User $usuario, ?string $exceptoSesionId): void
    {
        DB::table('sessions')
            ->where('user_id', $usuario->id)
            ->when($exceptoSesionId !== null, fn ($consulta) => $consulta->where('id', '!=', $exceptoSesionId))
            ->delete();

        $usuario->tokens()->delete();
    }

    /**
     * Deriva un nombre legible del dispositivo a partir del user agent, sin
     * dependencias nuevas. Formato `"{navegador} · {SO}"`.
     */
    private function dispositivoDesdeUserAgent(?string $ua): string
    {
        if ($ua === null || $ua === '') {
            return 'Desconocido';
        }

        $navegador = match (true) {
            str_contains($ua, 'Edg') => 'Edge',
            str_contains($ua, 'Chrome') => 'Chrome',
            str_contains($ua, 'Firefox') => 'Firefox',
            str_contains($ua, 'Safari') => 'Safari',
            default => 'Navegador',
        };

        $so = match (true) {
            str_contains($ua, 'Windows') => 'Windows',
            str_contains($ua, 'Android') => 'Android',
            str_contains($ua, 'iPhone'), str_contains($ua, 'iPad') => 'iOS',
            str_contains($ua, 'Mac') => 'macOS',
            str_contains($ua, 'Linux') => 'Linux',
            default => 'otro sistema',
        };

        return "{$navegador} · {$so}";
    }
}
