<?php

namespace App\Support;

use Laravel\Pennant\Feature;

class Modulos
{
    /**
     * Nombres de los módulos configurados.
     *
     * @return list<string>
     */
    public static function nombres(): array
    {
        return array_values(array_map(
            static fn ($nombre): string => (string) $nombre,
            (array) config('modulos'),
        ));
    }

    public static function existe(string $modulo): bool
    {
        return in_array($modulo, self::nombres(), true);
    }

    /**
     * Estado actual de cada módulo: ['ventas' => false, ...].
     *
     * @return array<string, bool>
     */
    public static function estado(): array
    {
        $estado = [];
        foreach (self::nombres() as $nombre) {
            $estado[$nombre] = Feature::active($nombre);
        }

        return $estado;
    }
}
