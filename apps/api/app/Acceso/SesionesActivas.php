<?php

namespace App\Acceso;

use App\Bitacora\Bitacora;
use App\Enums\AccionBitacora;
use App\Enums\EntidadBitacora;
use App\Enums\ResultadoBitacora;
use App\Events\UsuarioActualizado;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
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
    public function listar(User $usuario, ?string $sesionActualId): Collection
    {
        // La limpieza del driver `database` es por lotería, así que quedan filas
        // vencidas; mostramos solo las que sigan dentro de `session.lifetime`.
        $limite = now()->subMinutes((int) config('session.lifetime'))->getTimestamp();

        $sesiones = DB::table('sessions')
            ->where('user_id', $usuario->id)
            ->where('last_activity', '>=', $limite)
            ->get()
            ->map(fn (\stdClass $fila): array => [
                'id' => "panel:{$fila->id}",
                'tipo' => 'panel',
                'dispositivo' => $this->dispositivoDesdeUserAgent($fila->user_agent === null ? null : (string) $fila->user_agent),
                'ip' => $fila->ip_address === null ? null : (string) $fila->ip_address,
                'ultima_actividad' => Carbon::createFromTimestamp($fila->last_activity)->toISOString(),
                'actual' => $fila->id === $sesionActualId,
            ]);

        // Ningún Bearer llega a estas rutas (van bajo `ability:panel`), así que un
        // token de la app nunca es "la sesión actual".
        $tokens = $usuario->tokens
            ->map(fn (PersonalAccessToken $token): array => [
                'id' => "app:{$token->id}",
                'tipo' => 'app',
                'dispositivo' => $token->name === 'movil' ? 'App Android' : $token->name,
                'ip' => null,
                'ultima_actividad' => ($token->last_used_at ?? $token->created_at)?->toISOString(),
                'actual' => false,
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

        $borrado = match ($tipo) {
            'panel' => DB::table('sessions')
                ->where('id', $id)
                ->where('user_id', $usuario->id)
                ->delete() > 0,
            'app' => $usuario->tokens()->whereKey($id)->delete() > 0,
            default => false,
        };

        if ($borrado) {
            // Al cerrar una sesión del panel invalidamos el "recordarme" del
            // usuario: si no, su cookie `remember_web_*` lo volvería a autenticar.
            if ($tipo === 'panel') {
                $this->rotarRemember($usuario);
            }

            // El panel escucha este evento en su canal privado y recibe 401 ya.
            UsuarioActualizado::dispatch($usuario->id);

            Bitacora::auditar(
                AccionBitacora::CerrarSesiones,
                ResultadoBitacora::Exito,
                EntidadBitacora::Usuario,
                $usuario->id,
                auth()->user()?->name." cerró una sesión de {$usuario->name}",
                ['cantidad' => 1, 'sesiones' => [['tipo' => $tipo, 'id' => $id]]],
                $this->idEjecutor(),
            );
        }

        return $borrado;
    }

    /**
     * Cierra todas las sesiones del panel salvo `exceptoSesionId` y todos los
     * tokens de la app del usuario.
     */
    public function revocarTodas(User $usuario, ?string $exceptoSesionId): void
    {
        $consulta = DB::table('sessions')
            ->where('user_id', $usuario->id)
            ->when($exceptoSesionId !== null, fn ($consulta) => $consulta->where('id', '!=', $exceptoSesionId));

        $sesiones = $consulta->pluck('id')->all();
        $tokens = $usuario->tokens()->pluck('id')->all();

        DB::table('sessions')->whereIn('id', $sesiones)->delete();
        $usuario->tokens()->delete();

        // Siempre cierra sesiones del panel: invalidamos el "recordarme" en todos
        // los dispositivos y avisamos al panel para que reciba 401 de inmediato.
        $this->rotarRemember($usuario);
        UsuarioActualizado::dispatch($usuario->id);

        Bitacora::auditar(
            AccionBitacora::CerrarSesiones,
            ResultadoBitacora::Exito,
            EntidadBitacora::Usuario,
            $usuario->id,
            auth()->user()?->name." cerró todas las sesiones de {$usuario->name}",
            [
                'cantidad' => count($sesiones) + count($tokens),
                'sesiones' => $sesiones,
                'tokens' => $tokens,
            ],
            $this->idEjecutor(),
        );
    }

    private function idEjecutor(): ?int
    {
        $id = auth()->id();

        return is_int($id) ? $id : null;
    }

    /**
     * Rota el `remember_token` del usuario para invalidar sus cookies
     * `remember_web_*`; sin esto el "recordarme" lo volvería a autenticar.
     */
    private function rotarRemember(User $usuario): void
    {
        $usuario->setRememberToken(Str::random(60));
        $usuario->save();
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
