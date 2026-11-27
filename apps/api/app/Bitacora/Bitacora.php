<?php

namespace App\Bitacora;

use App\Enums\AccionBitacora;
use App\Enums\EntidadBitacora;
use App\Enums\ResultadoBitacora;
use App\Enums\TipoEventoBitacora;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Único punto de entrada de la bitácora: eventos del lote y auditoría general
 * (usuarios, catálogos, sesiones y módulos). Arma el documento (quién, cuándo,
 * la marca de captura tardía) y encola su escritura para después del commit,
 * delegándola en el escritor del contenedor (Mongo en producción, fake en
 * pruebas).
 *
 * Se llama dentro de la transacción de cada servicio. La escritura se difiere
 * con DB::afterCommit, así que solo corre si la operación hace commit: un
 * rollback nunca deja un documento huérfano (RNF-05, ADR-0003). Además va
 * envuelta en try/catch: la bitácora es secundaria y un fallo al escribirla
 * (p. ej. Mongo caído) nunca debe reventar la operación, que para entonces ya
 * confirmó.
 *
 * T-43 (imprevistos) y T-45 (ajustes de lote terminado) reúsan este mismo punto.
 */
class Bitacora
{
    /**
     * Claves que nunca se guardan en `datos`, ni siquiera dentro de `antes` o
     * `despues`: los snapshot de auditoría incluyen atributos completos.
     *
     * @var list<string>
     */
    private const CLAVES_SENSIBLES = [
        'password',
        'password_confirmation',
        'respuesta_secreta',
        'remember_token',
        'codigo',
        'token',
    ];

    /**
     * @param  array<string, mixed>  $datos
     */
    public static function registrar(
        ?TipoEventoBitacora $tipo,
        string $descripcion,
        ?int $loteId = null,
        ?int $areaId = null,
        array $datos = [],
        ?int $userId = null,
        ?CarbonInterface $ocurridoEn = null,
        ?CarbonInterface $registradoEn = null,
        ?AccionBitacora $accion = null,
        ?ResultadoBitacora $resultado = null,
        ?EntidadBitacora $entidad = null,
        ?int $entidadId = null,
    ): void {
        $registrado = $registradoEn ?? CarbonImmutable::now();
        $ocurrido = $ocurridoEn ?? $registrado;
        $datos = self::limpiarDatos($datos);

        $doc = [
            'lote_id' => $loteId,
            'area_id' => $areaId,
            'tipo' => $tipo?->value,
            'accion' => $accion?->value,
            'resultado' => $resultado?->value,
            'entidad' => $entidad?->value,
            'entidad_id' => $entidadId,
            'descripcion' => $descripcion,
            'datos' => $datos,
            'user_id' => $userId ?? auth()->id(),
            'ocurrido_en' => $ocurrido,
            'registrado_en' => $registrado,
            'captura_tardia' => $ocurrido->diffInMinutes($registrado) > (int) config('planta.umbral_captura_tardia'),
        ];

        DB::afterCommit(function () use ($doc, $tipo, $accion, $loteId): void {
            try {
                app(EscritorBitacora::class)->escribir($doc);
            } catch (Throwable $e) {
                Log::error('No se pudo escribir en la bitácora', [
                    'tipo' => $tipo->value ?? $accion?->value,
                    'lote_id' => $loteId,
                    'error' => $e->getMessage(),
                ]);
            }
        });
    }

    /**
     * Atajo para los eventos de auditoría, que no tienen tipo de evento de lote.
     * Todo sigue pasando por registrar(): aquí solo se fijan los argumentos que
     * no aplican a la auditoría.
     *
     * @param  array<string, mixed>  $datos
     */
    public static function auditar(
        AccionBitacora $accion,
        ResultadoBitacora $resultado,
        EntidadBitacora $entidad,
        ?int $entidadId,
        string $descripcion,
        array $datos = [],
        ?int $userId = null,
    ): void {
        self::registrar(
            tipo: null,
            descripcion: $descripcion,
            loteId: null,
            accion: $accion,
            resultado: $resultado,
            entidad: $entidad,
            entidadId: $entidadId,
            datos: $datos,
            userId: $userId,
        );
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

    /**
     * @param  array<string, mixed>  $datos
     * @return array<string, mixed>
     */
    private static function limpiarDatos(array $datos): array
    {
        $limpios = [];

        foreach ($datos as $clave => $valor) {
            if (in_array($clave, self::CLAVES_SENSIBLES, true)) {
                continue;
            }

            $limpios[$clave] = is_array($valor) ? self::limpiarDatos($valor) : $valor;
        }

        return $limpios;
    }
}
