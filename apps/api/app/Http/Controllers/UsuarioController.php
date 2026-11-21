<?php

namespace App\Http\Controllers;

use App\Enums\Rol;
use App\Events\UsuarioActualizado;
use App\Http\Requests\GuardarUsuarioRequest;
use App\Http\Resources\UsuarioResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class UsuarioController extends Controller
{
    /**
     * Los correos de baja viven en un dominio reservado para que nadie los reciba.
     */
    private const DOMINIO_BAJA = 'baja.aleacionprima.invalid';

    public function index(Request $request): JsonResponse
    {
        return response()->json(UsuarioResource::collection(
            User::query()
                ->with('areas')
                ->when(
                    ! $request->boolean('incluir_bajas'),
                    fn ($consulta) => $consulta->whereNull('dado_de_baja_en'),
                )
                ->orderBy('name')
                ->get(),
        ));
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
            $usuario->dado_de_baja_en !== null,
            Response::HTTP_UNPROCESSABLE_ENTITY,
            'Un usuario dado de baja ya no se edita.'
        );

        abort_if(
            array_key_exists('activo', $datos)
                && ! $request->boolean('activo')
                && $this->esElUsuarioAutenticado($request, $usuario),
            Response::HTTP_UNPROCESSABLE_ENTITY,
            'No puedes desactivarte a ti mismo.'
        );

        abort_if(
            $this->esElUsuarioAutenticado($request, $usuario)
                && $datos['rol'] !== Rol::Administrador->value,
            Response::HTTP_UNPROCESSABLE_ENTITY,
            'No puedes quitarte el rol de administrador.'
        );

        return DB::transaction(function () use ($datos, $usuario): JsonResponse {
            $usuario->update($this->atributos($datos, false));
            $usuario->syncRoles($datos['rol']);
            $usuario->areas()->sync($this->idsDeAreas($datos));
            UsuarioActualizado::dispatch($usuario->id);

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

        abort_if(
            $usuario->dado_de_baja_en !== null,
            Response::HTTP_UNPROCESSABLE_ENTITY,
            'Ese usuario ya está dado de baja.'
        );

        DB::transaction(function () use ($usuario): void {
            // La baja conserva la cuenta y su historial, pero libera el correo
            // (es único) y lo archiva para poder consultarlo después.
            $usuario->forceFill([
                'activo' => false,
                'dado_de_baja_en' => now(),
                'email_baja' => $usuario->email,
                'email' => "baja.{$usuario->id}@".self::DOMINIO_BAJA,
            ])->save();
            $usuario->tokens()->delete();
            UsuarioActualizado::dispatch($usuario->id);
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
