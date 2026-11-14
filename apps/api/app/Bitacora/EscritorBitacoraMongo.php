<?php

namespace App\Bitacora;

use App\Models\Bitacora as ModeloBitacora;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Escritor de producción: inserta en MongoDB, pero solo después de que la
 * transacción de Postgres haya hecho commit (RNF-05, ADR-0003), para no dejar
 * documentos huérfanos si la operación del lote se revierte.
 *
 * El try/catch de Bitacora::registrar() ya retornó cuando este callback corre,
 * así que la tolerancia a que Mongo falle se repite aquí: un fallo de Mongo
 * después del commit se registra en el log y nunca sube a la operación del lote.
 */
class EscritorBitacoraMongo implements EscritorBitacora
{
    /**
     * @param  array<string, mixed>  $doc
     */
    public function escribir(array $doc): void
    {
        DB::afterCommit(function () use ($doc): void {
            try {
                ModeloBitacora::query()->create($doc);
            } catch (Throwable $e) {
                Log::error('Bitácora: fallo al escribir en Mongo tras el commit', [
                    'error' => $e->getMessage(),
                ]);
            }
        });
    }
}
