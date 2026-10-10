<?php

declare(strict_types=1);

namespace App\Enums;

enum CategoriaInsumo: string
{
    case Metal = 'metal';
    case Moldeo = 'moldeo';
    case Empaque = 'empaque';
}
