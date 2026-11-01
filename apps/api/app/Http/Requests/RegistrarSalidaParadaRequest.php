<?php

namespace App\Http\Requests;

class RegistrarSalidaParadaRequest extends CapturaParadaRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'piezas_entregadas' => ['required', 'integer', 'min:0', 'max:9999'],
            'merma_material' => ['nullable', 'numeric', 'min:0', 'max:999999999', 'decimal:0,3'],
            'retorno_kg' => ['nullable', 'numeric', 'min:0', 'max:999999999', 'decimal:0,3'],
            'ocurrido_en' => ['nullable', 'date', 'before_or_equal:now'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            ...parent::messages(),
            'piezas_entregadas.required' => 'Indica cuántas piezas entregas.',
            'piezas_entregadas.integer' => 'Las piezas deben ser un número entero.',
            'piezas_entregadas.min' => 'Las piezas no pueden ser negativas.',
            'merma_material.min' => 'La merma de material no puede ser negativa.',
            'merma_material.decimal' => 'La merma de material admite hasta 3 decimales.',
            'retorno_kg.min' => 'El retorno no puede ser negativo.',
            'retorno_kg.decimal' => 'El retorno admite hasta 3 decimales.',
        ];
    }
}
