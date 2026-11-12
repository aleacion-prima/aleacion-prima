<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AjustarLoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * El campo y el valor se validan contra el renglón elegido en AjustarLote:
     * aquí solo se comprueba la forma del cuerpo.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'lote_area_id' => ['required_without:lote_insumo_id', 'integer', 'prohibits:lote_insumo_id'],
            'lote_insumo_id' => ['required_without:lote_area_id', 'integer', 'prohibits:lote_area_id'],
            'campo' => ['required', 'string', 'max:50'],
            'valor' => ['required'],
            'motivo' => ['required', 'string', 'max:1000'],
        ];
    }
}
