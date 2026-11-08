<?php

namespace App\Http\Requests;

class RegistrarEntradaParadaRequest extends CapturaParadaRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'piezas_recibidas' => ['required', 'integer', 'min:0', 'max:9999'],
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
            'piezas_recibidas.required' => 'Indica cuántas piezas recibiste.',
            'piezas_recibidas.integer' => 'Las piezas deben ser un número entero.',
            'piezas_recibidas.min' => 'Las piezas no pueden ser negativas.',
        ];
    }
}
