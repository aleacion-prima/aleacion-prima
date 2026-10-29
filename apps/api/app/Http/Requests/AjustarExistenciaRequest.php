<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AjustarExistenciaRequest extends FormRequest
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
            'cantidad_real' => ['required', 'numeric', 'gte:0'],
            'motivo' => ['required', 'string', 'max:1000'],
        ];
    }
}
