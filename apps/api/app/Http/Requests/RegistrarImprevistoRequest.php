<?php

namespace App\Http\Requests;

use App\Enums\TipoImprevisto;
use App\Models\LoteArea;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class RegistrarImprevistoRequest extends CapturaParadaRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'tipo' => ['required', Rule::enum(TipoImprevisto::class)],
            'descripcion' => ['required', 'string', 'max:1000'],
            'maquina' => ['nullable', 'string', 'max:120'],
            'piezas_afectadas' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'duracion_minutos' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'resuelto' => ['sometimes', 'boolean'],
            'ocurrido_en' => ['nullable', 'date', 'before_or_equal:now'],
        ];
    }

    /**
     * Un imprevisto es de un área que ya empezó: sin entrada registrada no hay dónde ligarlo.
     * Las paradas ya terminadas sí aceptan imprevistos (se capturan con retraso).
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validador): void {
                $parada = $this->route('parada');

                if ($parada instanceof LoteArea && $parada->entrada_ocurrido_en === null) {
                    $validador->errors()->add('parada', 'Esa área aún no empieza: registra primero su entrada.');
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            ...parent::messages(),
            'tipo.required' => 'Elige el tipo de imprevisto.',
            'tipo.enum' => 'El tipo debe ser falla de máquina, retraso de material, pieza defectuosa u otro.',
            'descripcion.required' => 'Describe qué pasó.',
            'descripcion.max' => 'La descripción no puede pasar de :max caracteres.',
            'maquina.max' => 'La máquina no puede pasar de :max caracteres.',
            'piezas_afectadas.integer' => 'Las piezas afectadas deben ser un número entero.',
            'piezas_afectadas.min' => 'Las piezas afectadas no pueden ser negativas.',
            'duracion_minutos.integer' => 'La duración debe ser un número entero de minutos.',
            'duracion_minutos.min' => 'La duración no puede ser negativa.',
        ];
    }
}
