<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegistrarEntradaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'cantidad' => ['required', 'numeric', 'gt:0'],
            'costo_unitario' => ['required', 'numeric', 'gte:0'],
            'proveedor' => ['required', 'string', 'max:255'],
            'lote_proveedor' => ['nullable', 'string', 'max:255'],
            'ocurrido_en' => ['sometimes', 'date'],
        ];
    }
}
