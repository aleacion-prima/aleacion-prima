<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CancelarLoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'motivo' => ['required', 'string', 'max:1000'],
            'devoluciones' => ['sometimes', 'array'],
            'devoluciones.*.insumo_id' => ['required', 'integer', 'distinct'],
            'devoluciones.*.cantidad' => ['required', 'numeric', 'gt:0', 'max:999999999', 'decimal:0,3'],
        ];
    }

    /**
     * @return array<int, array{insumo_id: int, cantidad: string}>
     */
    public function devoluciones(): array
    {
        return collect((array) $this->validated('devoluciones', []))
            ->map(fn (array $devolucion) => [
                'insumo_id' => (int) $devolucion['insumo_id'],
                'cantidad' => (string) $devolucion['cantidad'],
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'motivo.required' => 'Escribe el motivo de la cancelación.',
            'devoluciones.*.insumo_id.distinct' => 'No repitas un insumo en la devolución.',
            'devoluciones.*.cantidad.gt' => 'La cantidad a devolver debe ser mayor que cero.',
            'devoluciones.*.cantidad.decimal' => 'La cantidad admite hasta 3 decimales.',
        ];
    }
}
