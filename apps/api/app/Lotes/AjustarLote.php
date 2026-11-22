<?php

namespace App\Lotes;

use App\Bitacora\Bitacora;
use App\Enums\EstadoLote;
use App\Enums\TipoEventoBitacora;
use App\Enums\TipoMovimientoInsumo;
use App\Enums\TipoMovimientoProducto;
use App\Events\DisponibilidadActualizada;
use App\Events\LoteActualizado;
use App\Inventario\DetectorStockMinimo;
use App\Models\Ajuste;
use App\Models\Insumo;
use App\Models\Lote;
use App\Models\LoteArea;
use App\Models\LoteInsumo;
use App\Models\Producto;
use App\Models\User;
use Brick\Math\BigDecimal;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Corrige un dato ya capturado de un lote sin reabrirlo (ADR-0011): guarda el
 * valor anterior, el nuevo, el motivo y el responsable, y recalcula el costo
 * dentro de la misma transacción.
 */
class AjustarLote
{
    public function __construct(
        private CalculadorCostoLote $calculador,
        private DetectorStockMinimo $detector,
    ) {}

    /**
     * Campos ajustables de cada tipo de renglón.
     *
     * @var array<string, list<string>>
     */
    private const CAMPOS = [
        'parada' => [
            'piezas_recibidas',
            'piezas_entregadas',
            'merma_material',
            'entrada_ocurrido_en',
            'salida_ocurrido_en',
        ],
        'insumo' => ['cantidad', 'cantidad_devuelta'],
    ];

    /**
     * Nombres legibles para la descripción de la bitácora.
     *
     * @var array<string, string>
     */
    private const ETIQUETAS = [
        'piezas_recibidas' => 'piezas recibidas',
        'piezas_entregadas' => 'piezas entregadas',
        'merma_material' => 'merma de material',
        'entrada_ocurrido_en' => 'hora de entrada',
        'salida_ocurrido_en' => 'hora de salida',
        'cantidad' => 'cantidad asignada',
        'cantidad_devuelta' => 'cantidad devuelta',
    ];

    /**
     * @param  array<string, mixed>  $datos
     */
    public function ejecutar(Lote $lote, array $datos, User $usuario): Ajuste
    {
        return DB::transaction(function () use ($lote, $datos, $usuario): Ajuste {
            $lote = Lote::query()->lockForUpdate()->findOrFail($lote->id);

            $ajuste = isset($datos['lote_area_id'])
                ? $this->ajustarParada($lote, (int) $datos['lote_area_id'], $datos, $usuario)
                : $this->ajustarInsumo($lote, (int) $datos['lote_insumo_id'], $datos, $usuario);

            // El costo se recalcula aquí, no solo con el evento posterior al
            // commit: el lote queda consistente aunque el ajuste falle después.
            $this->calculador->recalcular($lote);

            LoteActualizado::dispatch($lote);

            return $ajuste;
        });
    }

    /**
     * @param  array<string, mixed>  $datos
     */
    private function ajustarParada(Lote $lote, int $paradaId, array $datos, User $usuario): Ajuste
    {
        $parada = $lote->paradas()->lockForUpdate()->find($paradaId);

        if (! $parada instanceof LoteArea) {
            $this->fallar('lote_area_id', 'Esa parada no pertenece al lote.');
        }

        if ($parada->entrada_ocurrido_en === null) {
            $this->fallar('lote_area_id', 'La parada todavía no empieza: no hay datos que ajustar.');
        }

        $campo = (string) $datos['campo'];
        $this->validarCampo($campo, self::CAMPOS['parada']);

        if ($parada->salida_ocurrido_en === null
            && in_array($campo, ['piezas_entregadas', 'merma_material', 'salida_ocurrido_en'], true)) {
            $this->fallar('campo', 'La parada todavía no tiene salida: ese dato aún no existe.');
        }

        $anterior = $parada->getAttribute($campo);
        $nuevo = $this->valor($campo, $datos['valor']);

        if ($this->esIgual($campo, $anterior, $nuevo)) {
            $this->fallar('valor', 'El valor nuevo ya es el actual: no hay nada que ajustar.');
        }

        $this->validarParada($parada, $campo, $nuevo);

        $parada->forceFill([$campo => $nuevo])->save();
        $this->recalcularMerma($parada, $campo);

        if ($campo === 'piezas_entregadas'
            && $lote->estado === EstadoLote::Terminado
            && $this->esUltimaParada($lote, $parada)) {
            $this->ajustarPiezasTerminadas($lote, (int) $anterior, (int) $nuevo, (string) $datos['motivo'], $usuario);
        }

        $ajuste = Ajuste::query()->create([
            'lote_area_id' => $parada->id,
            'campo' => $campo,
            'valores' => [
                'anterior' => $this->paraJson($anterior),
                'nuevo' => $this->paraJson($nuevo),
            ],
            'motivo' => $datos['motivo'],
            'user_id' => $usuario->id,
        ]);

        Bitacora::registrar(
            TipoEventoBitacora::Ajuste,
            "Ajuste de {$this->etiqueta($campo)} en la parada {$parada->orden} ({$parada->area?->nombre}): {$this->texto($anterior)} → {$this->texto($nuevo)}.",
            loteId: $lote->id,
            areaId: $parada->area_id,
            datos: [
                'lote_area_id' => $parada->id,
                'campo' => $campo,
                'anterior' => $this->paraJson($anterior),
                'nuevo' => $this->paraJson($nuevo),
                'motivo' => $datos['motivo'],
            ],
            userId: $usuario->id,
        );

        return $ajuste;
    }

    /**
     * @param  array<string, mixed>  $datos
     */
    private function ajustarInsumo(Lote $lote, int $asignadoId, array $datos, User $usuario): Ajuste
    {
        $asignado = $lote->insumos()->lockForUpdate()->find($asignadoId);

        if (! $asignado instanceof LoteInsumo) {
            $this->fallar('lote_insumo_id', 'Ese insumo no está asignado al lote.');
        }

        $campo = (string) $datos['campo'];
        $this->validarCampo($campo, self::CAMPOS['insumo']);

        $anterior = $asignado->getAttribute($campo);
        $nuevo = $this->valor($campo, $datos['valor']);

        if ($this->esIgual($campo, $anterior, $nuevo)) {
            $this->fallar('valor', 'El valor nuevo ya es el actual: no hay nada que ajustar.');
        }

        $cantidad = BigDecimal::of($campo === 'cantidad' ? (string) $nuevo : (string) $asignado->cantidad);
        $devuelta = BigDecimal::of($campo === 'cantidad_devuelta' ? (string) $nuevo : (string) $asignado->cantidad_devuelta);

        if ($devuelta->isGreaterThan($cantidad)) {
            $this->fallar('valor', "No puedes devolver más de lo asignado ({$cantidad}).");
        }

        $asignado->forceFill([$campo => $nuevo])->save();

        $insumo = $this->ajustarExistenciaInsumo(
            $asignado,
            $campo,
            BigDecimal::of((string) $anterior),
            BigDecimal::of((string) $nuevo),
            (string) $datos['motivo'],
            $lote,
            $usuario,
        );

        $ajuste = Ajuste::query()->create([
            'lote_insumo_id' => $asignado->id,
            'campo' => $campo,
            'valores' => [
                'anterior' => $this->paraJson($anterior),
                'nuevo' => $this->paraJson($nuevo),
            ],
            'motivo' => $datos['motivo'],
            'user_id' => $usuario->id,
        ]);

        Bitacora::registrar(
            TipoEventoBitacora::Ajuste,
            "Ajuste de {$this->etiqueta($campo)} de {$insumo->nombre} ({$insumo->codigo}): {$this->texto($anterior)} → {$this->texto($nuevo)} {$insumo->unidad}.",
            loteId: $lote->id,
            datos: [
                'lote_insumo_id' => $asignado->id,
                'insumo_id' => $asignado->insumo_id,
                'codigo' => $insumo->codigo,
                'campo' => $campo,
                'anterior' => $this->paraJson($anterior),
                'nuevo' => $this->paraJson($nuevo),
                'motivo' => $datos['motivo'],
            ],
            userId: $usuario->id,
        );

        return $ajuste;
    }

    /**
     * El insumo asignado ya salió del inventario al crearse el lote y lo
     * devuelto ya regresó al cierre. Corregir cualquiera de las dos cantidades
     * mueve la existencia por la diferencia: subir lo asignado resta y subir lo
     * devuelto suma. La existencia nunca puede quedar en negativo.
     */
    private function ajustarExistenciaInsumo(
        LoteInsumo $asignado,
        string $campo,
        BigDecimal $anterior,
        BigDecimal $nuevo,
        string $motivo,
        Lote $lote,
        User $usuario,
    ): Insumo {
        $diferencia = $campo === 'cantidad'
            ? $anterior->minus($nuevo)
            : $nuevo->minus($anterior);

        $insumo = Insumo::query()->lockForUpdate()->findOrFail($asignado->insumo_id);
        $existenciaAnterior = BigDecimal::of((string) $insumo->existencia);
        $existencia = $existenciaAnterior->plus($diferencia);

        if ($existencia->isNegative()) {
            $this->fallar(
                'valor',
                "No hay existencia suficiente de {$insumo->nombre}: quedaría en {$existencia} {$insumo->unidad}.",
            );
        }

        $insumo->movimientos()->create([
            'tipo' => TipoMovimientoInsumo::Ajuste,
            'cantidad' => (string) $diferencia,
            'costo_unitario' => $asignado->costo_unitario,
            'lote_proveedor' => $asignado->lote_proveedor,
            'lote_id' => $lote->id,
            'motivo' => $motivo,
            'user_id' => $usuario->id,
            'ocurrido_en' => now(),
        ]);

        $insumo->update(['existencia' => (string) $existencia]);

        $this->detector->revisar(
            $insumo->id,
            $existenciaAnterior->toFloat(),
            $existencia->toFloat(),
            (float) $insumo->stock_minimo,
        );

        return $insumo;
    }

    /**
     * Un lote terminado conserva sus piezas: si se corrigen las de la última
     * parada, se corrigen también las piezas terminadas y la existencia del
     * producto, con un movimiento de ajuste.
     */
    private function ajustarPiezasTerminadas(Lote $lote, int $anterior, int $nuevo, string $motivo, User $usuario): void
    {
        $producto = Producto::query()->lockForUpdate()->findOrFail($lote->producto_id);
        $diferencia = $nuevo - $anterior;
        $existencia = (int) $producto->existencia + $diferencia;

        if ($existencia < 0) {
            $this->fallar('valor', 'La existencia del producto quedaría en negativo.');
        }

        $producto->movimientos()->create([
            'tipo' => TipoMovimientoProducto::Ajuste,
            'cantidad' => $diferencia,
            'lote_id' => $lote->id,
            'motivo' => $motivo,
            'user_id' => $usuario->id,
            'ocurrido_en' => now(),
        ]);

        $producto->update(['existencia' => $existencia]);

        $lote->forceFill(['piezas_terminadas' => $nuevo])->save();

        DisponibilidadActualizada::dispatch($producto);
    }

    /**
     * @param  list<string>  $permitidos
     */
    private function validarCampo(string $campo, array $permitidos): void
    {
        if (! in_array($campo, $permitidos, true)) {
            $this->fallar('campo', 'Ese dato no se puede ajustar aquí.');
        }
    }

    private function valor(string $campo, mixed $valor): int|string|CarbonImmutable
    {
        $reglas = match ($campo) {
            'piezas_recibidas', 'piezas_entregadas' => ['required', 'integer', 'min:0'],
            'cantidad' => ['required', 'numeric', 'gt:0'],
            'merma_material', 'cantidad_devuelta' => ['required', 'numeric', 'min:0'],
            default => ['required', 'date'],
        };

        $validador = Validator::make(['valor' => $valor], ['valor' => $reglas]);

        if ($validador->fails()) {
            throw new ValidationException($validador);
        }

        $limpio = $validador->validated()['valor'];

        if (in_array($campo, ['piezas_recibidas', 'piezas_entregadas'], true)) {
            return (int) $limpio;
        }

        if (in_array($campo, ['entrada_ocurrido_en', 'salida_ocurrido_en'], true)) {
            return CarbonImmutable::parse((string) $limpio);
        }

        return (string) BigDecimal::of((string) $limpio);
    }

    private function esIgual(string $campo, mixed $anterior, mixed $nuevo): bool
    {
        if ($anterior === null) {
            return false;
        }

        if (in_array($campo, ['piezas_recibidas', 'piezas_entregadas'], true)) {
            return (int) $anterior === (int) $nuevo;
        }

        if (in_array($campo, ['entrada_ocurrido_en', 'salida_ocurrido_en'], true)) {
            return $anterior instanceof CarbonImmutable
                && $nuevo instanceof CarbonImmutable
                && $anterior->equalTo($nuevo);
        }

        // Los decimales se guardan con 3 posiciones: esa es la precisión real
        // del dato, así que una diferencia menor no cuenta como ajuste.
        return abs((float) $anterior - (float) $nuevo) < 0.0005;
    }

    private function validarParada(LoteArea $parada, string $campo, int|string|CarbonImmutable $nuevo): void
    {
        $recibidas = $campo === 'piezas_recibidas' ? (int) $nuevo : $parada->piezas_recibidas;
        $entregadas = $campo === 'piezas_entregadas' ? (int) $nuevo : $parada->piezas_entregadas;

        if ($recibidas !== null && $entregadas !== null && $entregadas > $recibidas) {
            $this->fallar('valor', "No puedes entregar más piezas de las que recibiste ({$recibidas}).");
        }

        $entrada = $campo === 'entrada_ocurrido_en' && $nuevo instanceof CarbonImmutable
            ? $nuevo
            : $parada->entrada_ocurrido_en;
        $salida = $campo === 'salida_ocurrido_en' && $nuevo instanceof CarbonImmutable
            ? $nuevo
            : $parada->salida_ocurrido_en;

        if ($entrada !== null && $salida !== null && $salida->lessThan($entrada)) {
            $this->fallar('valor', 'La salida no puede ser antes de la entrada.');
        }
    }

    /**
     * La merma en piezas es un dato derivado: se recalcula si cambian las
     * piezas recibidas o entregadas.
     */
    private function recalcularMerma(LoteArea $parada, string $campo): void
    {
        if (! in_array($campo, ['piezas_recibidas', 'piezas_entregadas'], true)) {
            return;
        }

        if ($parada->piezas_recibidas === null || $parada->piezas_entregadas === null) {
            return;
        }

        $parada->forceFill([
            'merma_piezas' => $parada->piezas_recibidas - $parada->piezas_entregadas,
        ])->save();
    }

    private function esUltimaParada(Lote $lote, LoteArea $parada): bool
    {
        return ! $lote->paradas()->where('orden', '>', $parada->orden)->exists();
    }

    private function paraJson(mixed $valor): mixed
    {
        return $valor instanceof DateTimeInterface ? $valor->format(DATE_ATOM) : $valor;
    }

    private function etiqueta(string $campo): string
    {
        return self::ETIQUETAS[$campo] ?? $campo;
    }

    /**
     * Texto legible para la descripción de la bitácora: fechas en d/m/Y H:i y
     * decimales sin ceros de relleno.
     */
    private function texto(mixed $valor): string
    {
        if ($valor instanceof DateTimeInterface) {
            return $valor->format('d/m/Y H:i');
        }

        if (is_int($valor)) {
            return (string) $valor;
        }

        $numero = (string) BigDecimal::of((string) $valor);

        // 200.000 y 200 son la misma cantidad: se muestra sin ceros de relleno.
        return str_contains($numero, '.') ? rtrim(rtrim($numero, '0'), '.') : $numero;
    }

    private function fallar(string $campo, string $mensaje): never
    {
        throw ValidationException::withMessages([$campo => $mensaje]);
    }
}
