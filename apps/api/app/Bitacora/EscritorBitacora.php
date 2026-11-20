<?php

namespace App\Bitacora;

/**
 * Qué escribe la bitácora (el documento ya armado), separado de cómo y cuándo
 * se escribe. El cuándo (después del commit de Postgres, ADR-0003) lo decide
 * Bitacora::registrar(). En producción lo implementa EscritorBitacoraMongo; en
 * pruebas, EscritorBitacoraFake.
 */
interface EscritorBitacora
{
    /**
     * @param  array<string, mixed>  $doc
     */
    public function escribir(array $doc): void;
}
