<?php

namespace App\Http\Requests;

use App\Enums\AccionBitacora;
use App\Enums\EntidadBitacora;
use App\Enums\ResultadoBitacora;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ConsultarBitacoraRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'accion' => ['sometimes', Rule::enum(AccionBitacora::class)],
            'resultado' => ['sometimes', Rule::enum(ResultadoBitacora::class)],
            'entidad' => ['sometimes', Rule::enum(EntidadBitacora::class)],
            'usuario' => ['sometimes', 'integer', 'exists:users,id'],
            'desde' => ['sometimes', 'date'],
            'hasta' => ['sometimes', 'date'],
            'ocultar_logins' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('ocultar_logins')) {
            $this->merge(['ocultar_logins' => $this->boolean('ocultar_logins')]);
        }
    }
}
