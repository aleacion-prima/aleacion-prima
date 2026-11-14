<?php

namespace App\Bitacora;

use App\Enums\TipoEventoBitacora;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Único punto de entrada de la bitácora del lote. Arma el documento (quién,
 * cuándo, la marca de captura tardía) y delega la escritura en el escritor del
 * contenedor (Mongo en producción, fake en pruebas).
 *
 * La escritura va envuelta en try/catch: la bitácora es secundaria, así que un
 * fallo al registrarla nunca debe revertir la operación del lote (RNF-05). Se
 * llama dentro de la transacción de cada servicio; el escritor de Mongo se
 * encarga de diferir la inserción real a después del commit (ADR-0003).
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

        try {
            app(EscritorBitacora::class)->escribir($doc);
        } catch (Throwable $e) {
            Log::error('No se pudo escribir en la bitácora', [
                'tipo' => $tipo->value,
                'lote_id' => $loteId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Reemplaza el escritor del contenedor por un fake síncrono y lo devuelve
     * para hacer aserciones en los tests.
     */
    public static function fake(): EscritorBitacoraFake
    {
        $fake = new EscritorBitacoraFake;
        app()->instance(EscritorBitacora::class, $fake);

        return $fake;
    }
}
