<?php

namespace App\Bitacora;

use App\Enums\TipoEventoBitacora;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Único punto de entrada de la bitácora del lote. Arma el documento (quién,
 * cuándo, la marca de captura tardía) y encola su escritura para después del
 * commit, delegándola en el escritor del contenedor (Mongo en producción, fake
 * en pruebas).
 *
 * Se llama dentro de la transacción de cada servicio. La escritura se difiere
 * con DB::afterCommit, así que solo corre si la operación del lote hace commit:
 * un rollback nunca deja un documento huérfano (RNF-05, ADR-0003). Además va
 * envuelta en try/catch: la bitácora es secundaria y un fallo al escribirla
 * (p. ej. Mongo caído) nunca debe reventar la operación, que para entonces ya
 * confirmó.
 *
 * T-43 (imprevistos) y T-45 (ajustes de lote terminado) reúsan este mismo punto.
 */
class Bitacora
{
    /**
     * @param  array<string, mixed>  $datos
     */
    public static function registrar(
        TipoEventoBitacora $tipo,
        string $descripcion,
        ?int $loteId = null,
        ?int $areaId = null,
        array $datos = [],
        ?int $userId = null,
        ?CarbonInterface $ocurridoEn = null,
        ?CarbonInterface $registradoEn = null,
    ): void {
        $registrado = $registradoEn ?? CarbonImmutable::now();
        $ocurrido = $ocurridoEn ?? $registrado;

        $doc = [
            'lote_id' => $loteId,
            'area_id' => $areaId,
            'tipo' => $tipo->value,
            'descripcion' => $descripcion,
            'datos' => $datos,
            'user_id' => $userId ?? auth()->id(),
            'ocurrido_en' => $ocurrido,
            'registrado_en' => $registrado,
            'captura_tardia' => $ocurrido->diffInMinutes($registrado) > (int) config('planta.umbral_captura_tardia'),
        ];

        DB::afterCommit(function () use ($doc, $tipo, $loteId): void {
            try {
                app(EscritorBitacora::class)->escribir($doc);
            } catch (Throwable $e) {
                Log::error('No se pudo escribir en la bitácora', [
                    'tipo' => $tipo->value,
                    'lote_id' => $loteId,
                    'error' => $e->getMessage(),
                ]);
            }
        });
    }

    /**
     * Reemplaza el escritor del contenedor por un fake en memoria y lo devuelve
     * para hacer aserciones en los tests. El after-commit de registrar() sigue
     * vigente: el fake solo captura eventos de operaciones que hacen commit.
     */
    public static function fake(): EscritorBitacoraFake
    {
        $fake = new EscritorBitacoraFake;
        app()->instance(EscritorBitacora::class, $fake);

        return $fake;
    }
}
