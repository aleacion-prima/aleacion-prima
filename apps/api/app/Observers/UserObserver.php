<?php

namespace App\Observers;

use App\Enums\EntidadBitacora;
use Illuminate\Database\Eloquent\Model;

class UserObserver
{
    use RegistraAuditoria;

    protected function entidad(): EntidadBitacora
    {
        return EntidadBitacora::Usuario;
    }

    protected function nombre(Model $modelo): string
    {
        return (string) $modelo->getAttribute('name');
    }
}
