<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GuardarPreguntaSecretaRequest extends FormRequest
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
            'pregunta' => ['required', 'string', Rule::in(array_values((array) config('preguntas-secretas.preguntas')))],
            'respuesta' => ['required', 'string', 'min:2', 'max:191'],
        ];
    }
}
