<?php

namespace App\Bitacora;

/**
 * Qué escribe la bitácora (el documento ya armado), separado de cómo y cuándo
 * se escribe. En producción lo implementa EscritorBitacoraMongo (difiere a
 * después del commit de Postgres, ADR-0003); en pruebas, EscritorBitacoraFake.
 */
interface EscritorBitacora
{
    /**
     * @param  array<string, mixed>  $doc
     */
    public function escribir(array $doc): void;
}
