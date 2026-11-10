<?php

namespace App\Broadcasting;

use App\Enums\Rol;
use App\Models\User;

class CanalTablero
{
    public function join(User $usuario): bool
    {
        return $usuario->activo && $usuario->hasRole(Rol::Administrador);
    }
}
