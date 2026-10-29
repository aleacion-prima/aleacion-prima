<?php

namespace App\Http\Controllers;

use App\Enums\Rol;
use App\Http\Requests\GuardarUsuarioRequest;
use App\Http\Resources\UsuarioResource;
use App\Models\Area;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class UsuarioController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(UsuarioResource::collection(
            User::query()->with('areas')->orderBy('name')->get(),
        ));
    }

    public function areas(): JsonResponse
    {
        return response()->json(Area::query()
            ->where('activo', true)
            ->orderBy('orden_catalogo')
            ->get(['id', 'codigo', 'nombre']));
    }

    public function store(GuardarUsuarioRequest $request): JsonResponse
    {
        return DB::transaction(function () use ($request): JsonResponse {
            $datos = $request->validated();
            $usuario = User::query()->create($this->atributos($datos));
            $usuario->syncRoles($datos['rol']);
            $usuario->areas()->sync($this->idsDeAreas($datos));

            return (new UsuarioResource($usuario->load('areas')))
                ->response()
                ->setStatusCode(Response::HTTP_CREATED);
        });
    }

    public function update(GuardarUsuarioRequest $request, User $usuario): JsonResponse
    {
        $datos = $request->validated();

        abort_if(
            array_key_exists('activo', $datos)
                && ! $request->boolean('activo')
                && $this->esElUsuarioAutenticado($request, $usuario),
            Response::HTTP_UNPROCESSABLE_ENTITY,
            'No puedes desactivarte a ti mismo.'
        );

        return DB::transaction(function () use ($datos, $usuario): JsonResponse {
            $usuario->update($this->atributos($datos, false));
            $usuario->syncRoles($datos['rol']);
            $usuario->areas()->sync($this->idsDeAreas($datos));

            return (new UsuarioResource($usuario->load('areas')))->response();
        });
    }

    public function destroy(Request $request, User $usuario): Response
    {
        abort_if(
            $this->esElUsuarioAutenticado($request, $usuario),
            Response::HTTP_UNPROCESSABLE_ENTITY,
            'No puedes darte de baja a ti mismo.'
        );

        DB::transaction(function () use ($usuario): void {
            $usuario->forceFill(['activo' => false])->save();
            $usuario->tokens()->delete();
        });

        return response()->noContent();
    }

    private function esElUsuarioAutenticado(Request $request, User $usuario): bool
    {
        return $request->user()?->getAuthIdentifier() === $usuario->getKey();
    }

    /**
     * @param  array<string, mixed>  $datos
     * @return array<string, mixed>
     */
    private function atributos(array $datos, bool $esNuevo = true): array
    {
        $atributos = [
            'name' => $datos['name'],
            'email' => $datos['email'],
        ];

        if ($esNuevo || isset($datos['password'])) {
            $atributos['password'] = $datos['password'];
        }

        if (array_key_exists('activo', $datos)) {
            $atributos['activo'] = $datos['activo'];
        }

        return $atributos;
    }

    /**
     * @param  array<string, mixed>  $datos
     * @return array<int, int>
     */
    private function idsDeAreas(array $datos): array
    {
        return $datos['rol'] === Rol::Encargado->value
            ? array_map('intval', $datos['area_ids'] ?? [])
            : [];
    }
}
