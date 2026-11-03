<?php

namespace App\Http\Requests;

use App\Enums\Rol;
use App\Models\LoteArea;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;

abstract class CapturaParadaRequest extends FormRequest
{
    public function authorize(): bool
    {
        $usuario = $this->user();
        $parada = $this->route('parada');

        if (! $usuario instanceof User || ! $parada instanceof LoteArea) {
            return false;
        }

        return $usuario->hasRole(Rol::Administrador)
            || $usuario->areas()->whereKey($parada->area_id)->exists();
    }

    public function ocurridoEn(): ?CarbonImmutable
    {
        return $this->filled('ocurrido_en')
            ? CarbonImmutable::parse($this->string('ocurrido_en')->toString())
            : null;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'ocurrido_en.date' => 'La hora no es válida.',
            'ocurrido_en.before_or_equal' => 'La hora no puede estar en el futuro.',
        ];
    }
}
