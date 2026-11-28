<?php

declare(strict_types=1);

namespace App\Enums;

enum TipoImprevisto: string
{
    case FallaMaquina = 'falla_maquina';
    case RetrasoMaterial = 'retraso_material';
    case PiezaDefectuosa = 'pieza_defectuosa';
    case Otro = 'otro';

    public function etiqueta(): string
    {
        return match ($this) {
            self::FallaMaquina => 'Falla de máquina',
            self::RetrasoMaterial => 'Retraso de material',
            self::PiezaDefectuosa => 'Pieza defectuosa',
            self::Otro => 'Otro',
        };
    }
}
