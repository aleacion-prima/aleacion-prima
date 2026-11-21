<?php

namespace App\Bitacora;

use App\Models\Bitacora as ModeloBitacora;

/**
 * Escritor de producción: inserta el documento en MongoDB. El diferido a
 * después del commit y la tolerancia a fallos viven en Bitacora::registrar()
 * (ADR-0003), así que aquí solo queda la inserción.
 */
class EscritorBitacoraMongo implements EscritorBitacora
{
    /**
     * @param  array<string, mixed>  $doc
     */
    public function escribir(array $doc): void
    {
        ModeloBitacora::query()->create($doc);
    }
}
