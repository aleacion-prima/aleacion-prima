<?php

namespace App\Http\Controllers;

use App\Acceso\SesionesActivas;
use App\Http\Resources\SesionActivaResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/**
 * Sesiones activas del usuario: las del panel (cookie) y los tokens de la app.
 * El id compuesto `tipo:id` que viaja en la URL indica de qué tabla borrar; la
 * lógica vive en el servicio SesionesActivas.
 */
class SesionActivaController extends Controller
{
    public function __construct(private readonly SesionesActivas $sesiones) {}

    /**
     * Mis sesiones (las del usuario autenticado).
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $usuario = $this->usuario($request);

        return SesionActivaResource::collection(
            $this->sesiones->listar($usuario, $this->sesionActualId($request)),
        );
    }

    /**
     * Cierra una de mis sesiones (del panel o un token).
     */
    public function destroy(Request $request, string $id): Response
    {
        $usuario = $this->usuario($request);

        abort_unless($this->sesiones->revocar($usuario, $id), Response::HTTP_NOT_FOUND);

        return response()->noContent();
    }

    /**
     * Cierra todas mis sesiones menos la actual.
     */
    public function destroyOtras(Request $request): Response
    {
        $usuario = $this->usuario($request);

        $this->sesiones->revocarTodas($usuario, $this->sesionActualId($request));

        return response()->noContent();
    }

    /**
     * Admin: sesiones de otro usuario. Aquí no hay "actual" propia.
     */
    public function indexDe(User $usuario): AnonymousResourceCollection
    {
        return SesionActivaResource::collection($this->sesiones->listar($usuario, null));
    }

    /**
     * Admin: cierra una sesión de otro usuario.
     */
    public function destroyDe(User $usuario, string $id): Response
    {
        abort_unless($this->sesiones->revocar($usuario, $id), Response::HTTP_NOT_FOUND);

        return response()->noContent();
    }

    /**
     * Admin: cierra todas las sesiones de otro usuario.
     */
    public function destroyTodasDe(User $usuario): Response
    {
        $this->sesiones->revocarTodas($usuario, null);

        return response()->noContent();
    }

    /**
     * Id de la sesión del panel que hace la petición, o null. Las peticiones de
     * la API no siempre arrancan sesión (p. ej. con Bearer o en pruebas), así que
     * se comprueba antes de leerla.
     */
    private function sesionActualId(Request $request): ?string
    {
        return $request->hasSession() ? $request->session()->getId() : null;
    }

    private function usuario(Request $request): User
    {
        $usuario = $request->user();
        abort_unless($usuario instanceof User, Response::HTTP_UNAUTHORIZED);

        return $usuario;
    }
}
