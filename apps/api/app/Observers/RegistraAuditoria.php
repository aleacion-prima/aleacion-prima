<?php

namespace App\Observers;

use App\Bitacora\Bitacora;
use App\Enums\AccionBitacora;
use App\Enums\EntidadBitacora;
use App\Enums\ResultadoBitacora;
use Illuminate\Database\Eloquent\Model;

/**
 * Parte común de los observers de catálogos y usuarios. Cada observer solo
 * define qué entidad audita y cómo se llama; el registro pasa siempre por
 * Bitacora::auditar().
 */
trait RegistraAuditoria
{
    abstract protected function entidad(): EntidadBitacora;

    abstract protected function nombre(Model $modelo): string;

    public function created(Model $modelo): void
    {
        Bitacora::auditar(
            AccionBitacora::Crear,
            ResultadoBitacora::Exito,
            $this->entidad(),
            (int) $modelo->getKey(),
            $this->actor().' creó el '.$this->entidad()->value.' '.$this->nombre($modelo),
            ['despues' => $modelo->getAttributes()],
        );
    }

    public function updated(Model $modelo): void
    {
        $cambios = $modelo->getChanges();
        unset($cambios['updated_at']);

        if ($cambios === []) {
            return;
        }

        Bitacora::auditar(
            AccionBitacora::Actualizar,
            ResultadoBitacora::Exito,
            $this->entidad(),
            (int) $modelo->getKey(),
            $this->actor().' actualizó el '.$this->entidad()->value.' '.$this->nombre($modelo),
            [
                'antes' => array_intersect_key($modelo->getOriginal(), $cambios),
                'despues' => $cambios,
            ],
        );
    }

    public function deleted(Model $modelo): void
    {
        Bitacora::auditar(
            AccionBitacora::Eliminar,
            ResultadoBitacora::Exito,
            $this->entidad(),
            (int) $modelo->getKey(),
            $this->actor().' eliminó el '.$this->entidad()->value.' '.$this->nombre($modelo),
            ['antes' => $modelo->getOriginal()],
        );
    }

    private function actor(): string
    {
        return auth()->user()->name ?? 'Sistema';
    }
}
