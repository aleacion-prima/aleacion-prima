<?php

namespace App\Observers;

use App\Enums\EntidadBitacora;
use Illuminate\Database\Eloquent\Model;

class ProductoObserver
{
    use RegistraAuditoria;

    protected function entidad(): EntidadBitacora
    {
        return EntidadBitacora::Producto;
    }

    protected function nombre(Model $modelo): string
    {
        return (string) $modelo->getAttribute('nombre');
    }
}
