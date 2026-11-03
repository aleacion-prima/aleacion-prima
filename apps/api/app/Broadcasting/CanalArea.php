<?php

namespace App\Broadcasting;

use App\Enums\Rol;
use App\Models\User;

class CanalArea
{
    public function join(User $usuario, int $area): bool
    {
        if (! $usuario->activo) {
            return false;
        }

        return $usuario->hasRole(Rol::Administrador)
            || $usuario->areas()->whereKey($area)->exists();
    }
}
