<?php

namespace App\Http\Controllers;

use App\Enums\Rol;
use App\Http\Resources\LoteResource;
use App\Http\Resources\UsuarioResource;
use App\Lotes\CapturaParada;
use App\Lotes\CreadorLote;
use App\Models\Lote;
use App\Models\LoteArea;
use App\Models\Producto;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class DebugController extends Controller
{
    public function usuarios(): AnonymousResourceCollection
    {
        return UsuarioResource::collection(
            User::query()->where('activo', true)
                ->with('areas')
                ->orderBy('name')
                ->get()
        );
    }

    public function entrarComo(Request $request, User $user): UsuarioResource
    {
        abort_unless($user->activo, 422, 'El usuario está desactivado.');

        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        return new UsuarioResource($user->load('areas'));
    }

    public function crearLote(Request $request, CreadorLote $creador): JsonResponse
    {
        $usuario = $this->usuario($request);
        abort_unless($usuario->hasRole(Rol::Administrador), Response::HTTP_FORBIDDEN);

        $datos = $request->validate([
            'producto_id' => ['sometimes', 'integer', 'exists:productos,id'],
            'cantidad' => ['sometimes', 'integer', 'min:1'],
        ]);

        $producto = isset($datos['producto_id'])
            ? Producto::query()->findOrFail($datos['producto_id'])
            : Producto::query()->where('activo', true)->orderBy('id')->firstOrFail();

        $lote = $creador->crear($producto, $datos['cantidad'] ?? 10, null, $usuario);

        return (new LoteResource($lote))->response()->setStatusCode(Response::HTTP_CREATED);
    }

    public function avanzar(Request $request, Lote $lote, CapturaParada $captura): LoteResource
    {
        $usuario = $this->usuario($request);

        $datos = $request->validate([
            'hasta' => ['sometimes', 'in:siguiente,final'],
            'merma' => ['sometimes', 'integer', 'min:0'],
            'faltante' => ['sometimes', 'integer', 'min:0'],
        ]);

        $merma = $datos['merma'] ?? 0;
        $faltante = $datos['faltante'] ?? 0;
        $pasos = $this->pasosPendientes($lote);

        abort_if($pasos === [], Response::HTTP_UNPROCESSABLE_ENTITY, 'El lote ya terminó su recorrido.');

        if (($datos['hasta'] ?? 'siguiente') === 'siguiente') {
            $pasos = [$pasos[0]];
        }

        $hora = $this->horaInicial($lote, count($pasos));
        $paso = min(30 * 60, intdiv((int) $hora->diffInSeconds(CarbonImmutable::now()), count($pasos)));

        foreach ($pasos as [$tipo, $parada]) {
            $parada = $parada->fresh();
            abort_unless($this->puedeCapturar($usuario, $parada), Response::HTTP_FORBIDDEN);

            if ($tipo === 'entrada') {
                $anterior = $lote->paradas()->where('orden', '<', $parada->orden)->reorder('orden', 'desc')->first();
                $piezas = ($anterior->piezas_entregadas ?? $lote->cantidad_objetivo) - $faltante;
                $captura->entrada($lote, $parada, max($piezas, 0), $hora, $usuario);
                $faltante = 0;
            } else {
                $captura->salida($lote, $parada, max((int) $parada->piezas_recibidas - $merma, 0), null, null, $hora, $usuario);
                $merma = 0;
            }

            $hora = $hora->addSeconds($paso);
        }

        return new LoteResource($lote->fresh()?->load(['producto', 'areaActual', 'paradas.area', 'insumos.insumo']));
    }

    public function reiniciar(Request $request): Response
    {
        abort_unless($this->usuario($request)->hasRole(Rol::Administrador), Response::HTTP_FORBIDDEN);

        Artisan::call('migrate:fresh', ['--seed' => true, '--force' => true]);

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->noContent();
    }

    /**
     * @return list<array{0: 'entrada'|'salida', 1: LoteArea}>
     */
    private function pasosPendientes(Lote $lote): array
    {
        $pasos = [];

        foreach ($lote->paradas()->orderBy('orden')->get() as $parada) {
            if ($parada->entrada_ocurrido_en === null) {
                $pasos[] = ['entrada', $parada];
            }

            if ($parada->salida_ocurrido_en === null) {
                $pasos[] = ['salida', $parada];
            }
        }

        return $pasos;
    }

    private function horaInicial(Lote $lote, int $pasos): CarbonImmutable
    {
        $minima = CarbonImmutable::now()->subMinutes(30 * $pasos);
        $ultima = $lote->paradas()->get()
            ->flatMap(fn (LoteArea $parada) => [$parada->entrada_ocurrido_en, $parada->salida_ocurrido_en])
            ->filter()
            ->max();

        return $ultima !== null && $ultima->greaterThan($minima) ? CarbonImmutable::parse($ultima) : $minima;
    }

    private function puedeCapturar(User $usuario, LoteArea $parada): bool
    {
        return $usuario->hasRole(Rol::Administrador)
            || $usuario->areas()->whereKey($parada->area_id)->exists();
    }

    private function usuario(Request $request): User
    {
        $usuario = $request->user();
        abort_unless($usuario instanceof User, Response::HTTP_UNAUTHORIZED);

        return $usuario;
    }
}
