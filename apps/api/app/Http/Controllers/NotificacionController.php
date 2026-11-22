<?php

namespace App\Http\Controllers;

use App\Http\Resources\NotificacionResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/**
 * Notificaciones del administrador autenticado. Siempre opera sobre la relación
 * del usuario en sesión, así nadie toca las ajenas.
 */
class NotificacionController extends Controller
{
    /**
     * Lista paginada; con ?no_leidas=1 solo las no leídas.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $consulta = $this->usuario($request)->notifications();

        if ($request->boolean('no_leidas')) {
            $consulta->whereNull('read_at');
        }

        if ($request->filled('tipo')) {
            $consulta->where('data->tipo', $request->string('tipo')->toString());
        }

        if ($request->boolean('leidas')) {
            $consulta->whereNotNull('read_at');
        }

        return NotificacionResource::collection($consulta->paginate(15));
    }

    /**
     * Marca una notificación como leída. El findOrFail sobre la relación garantiza
     * que sea del usuario (404 si no).
     */
    public function leida(Request $request, string $id): Response
    {
        $this->usuario($request)->notifications()->findOrFail($id)->markAsRead();

        return response()->noContent();
    }

    /**
     * Marca todas las no leídas como leídas.
     */
    public function leidas(Request $request): Response
    {
        // Un solo UPDATE sobre la relación en vez de cargar y marcar una a una.
        $this->usuario($request)->unreadNotifications()->update(['read_at' => now()]);

        return response()->noContent();
    }

    private function usuario(Request $request): User
    {
        $usuario = $request->user();
        abort_unless($usuario instanceof User, Response::HTTP_UNAUTHORIZED);

        return $usuario;
    }
}
