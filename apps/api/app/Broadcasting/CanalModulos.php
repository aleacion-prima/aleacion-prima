<?php

namespace App\Broadcasting;

use App\Models\User;

class CanalModulos
{
    public function join(User $usuario): bool
    {
        return $usuario->activo;
    }
}
