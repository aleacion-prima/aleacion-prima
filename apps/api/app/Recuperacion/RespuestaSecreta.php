<?php

namespace App\Recuperacion;

class RespuestaSecreta
{
    /**
     * Normaliza la respuesta para compararla sin importar mayúsculas ni espacios
     * de más: minúsculas, sin espacios al inicio/fin y espacios internos colapsados.
     */
    public static function normalizar(string $valor): string
    {
        return (string) preg_replace('/\s+/u', ' ', trim(mb_strtolower($valor)));
    }
}
