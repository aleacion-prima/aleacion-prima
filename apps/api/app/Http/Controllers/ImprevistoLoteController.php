<?php

namespace App\Http\Controllers;

use App\Enums\Rol;
use App\Enums\TipoImprevisto;
use App\Events\ImprevistoActualizado;
use App\Events\LoteActualizado;
use App\Http\Requests\RegistrarImprevistoRequest;
use App\Http\Requests\ResolverImprevistoRequest;
use App\Http\Resources\ImprevistoResource;
use App\Imprevistos\AlmacenImprevistos;
use App\Imprevistos\RegistradorImprevisto;
use App\Models\Area;
use App\Models\Imprevisto;
use App\Models\Lote;
use App\Models\LoteArea;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Response;

class ImprevistoLoteController extends Controller
{
    public function index(Lote $lote, AlmacenImprevistos $almacen): AnonymousResourceCollection
    {
        return ImprevistoResource::collection($this->conNombres($almacen->delLote($lote->id)));
    }

    public function store(
        RegistrarImprevistoRequest $request,
        Lote $lote,
        LoteArea $parada,
        RegistradorImprevisto $registrador,
    ): JsonResponse {
        $imprevisto = $registrador->registrar(
            $lote,
            $parada,
            TipoImprevisto::from($request->string('tipo')->toString()),
            $request->string('descripcion')->toString(),
            $request->filled('maquina') ? $request->string('maquina')->toString() : null,
            $request->filled('piezas_afectadas') ? $request->integer('piezas_afectadas') : null,
            $request->filled('duracion_minutos') ? $request->integer('duracion_minutos') : null,
            $request->boolean('resuelto'),
            $request->ocurridoEn(),
            $this->usuario($request->user()),
        );

        $this->conNombres(collect([$imprevisto]));

        return (new ImprevistoResource($imprevisto))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /** Marca (o desmarca) un imprevisto como resuelto. */
    public function update(
        ResolverImprevistoRequest $request,
        Lote $lote,
        string $imprevisto,
        AlmacenImprevistos $almacen,
    ): ImprevistoResource {
        $encontrado = $almacen->buscar($lote->id, $imprevisto) ?? abort(Response::HTTP_NOT_FOUND);
        $usuario = $this->usuario($request->user());

        abort_unless(
            $usuario->hasRole(Rol::Administrador) || $usuario->areas()->whereKey($encontrado->area_id)->exists(),
            Response::HTTP_FORBIDDEN,
        );

        $almacen->marcarResuelto($encontrado, $request->boolean('resuelto'));
        ImprevistoActualizado::dispatch($lote, (string) $encontrado->getKey(), $request->boolean('resuelto'));
        $this->conNombres(collect([$encontrado]));

        LoteActualizado::dispatch($lote);

        return new ImprevistoResource($encontrado);
    }

    /**
     * @param  Collection<int, Imprevisto>  $imprevistos
     * @return Collection<int, Imprevisto>
     */
    private function conNombres(Collection $imprevistos): Collection
    {
        $areas = Area::query()->whereIn('id', $imprevistos->pluck('area_id')->unique()->all())->get()->keyBy('id');
        $usuarios = User::query()->whereIn('id', $imprevistos->pluck('user_id')->unique()->all())->get()->keyBy('id');

        foreach ($imprevistos as $imprevisto) {
            $imprevisto->setRelation('area', $areas->get($imprevisto->area_id));
            $imprevisto->setRelation('registrador', $usuarios->get($imprevisto->user_id));
        }

        return $imprevistos;
    }

    private function usuario(mixed $usuario): User
    {
        abort_unless($usuario instanceof User, Response::HTTP_UNAUTHORIZED);

        return $usuario;
    }
}
