<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class AreaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('codigo'))) {
            $this->merge(['codigo' => Str::upper(trim($this->input('codigo')))]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $area = $this->route('area');
        $tarifa = ['required', 'numeric', 'min:0', 'max:9999999999', 'decimal:0,4'];

        return [
            'codigo' => ['required', 'string', 'max:10', 'regex:/^[A-Z0-9]+$/', Rule::unique('areas', 'codigo')->ignore($area)],
            'nombre' => ['required', 'string', 'max:255', Rule::unique('areas', 'nombre')->ignore($area)],
            'descripcion' => ['nullable', 'string', 'max:1000'],
            'tarifa_mano_obra_hora' => $tarifa,
            'costo_maquina_hora' => $tarifa,
            'obligatoria' => ['required', 'boolean'],
            'orden_catalogo' => ['required', 'integer', 'min:1', Rule::unique('areas', 'orden_catalogo')->ignore($area)],
            'activo' => $this->isMethod('POST') ? ['sometimes', 'boolean'] : ['required', 'boolean'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validador): void {
                if ($this->boolean('obligatoria') && $this->has('activo') && ! $this->boolean('activo')) {
                    $validador->errors()->add('activo', 'Un área obligatoria no puede desactivarse: todo recorrido la incluye.');
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'codigo.required' => 'El código es obligatorio.',
            'codigo.max' => 'El código no puede pasar de :max caracteres.',
            'codigo.regex' => 'El código solo lleva letras mayúsculas y números, sin espacios.',
            'codigo.unique' => 'Ya existe un área con ese código.',
            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.unique' => 'Ya existe un área con ese nombre.',
            'tarifa_mano_obra_hora.required' => 'La tarifa de mano de obra es obligatoria.',
            'tarifa_mano_obra_hora.numeric' => 'La tarifa de mano de obra debe ser un número.',
            'tarifa_mano_obra_hora.min' => 'La tarifa de mano de obra no puede ser negativa.',
            'tarifa_mano_obra_hora.decimal' => 'La tarifa de mano de obra admite hasta :decimal decimales.',
            'costo_maquina_hora.required' => 'El costo de máquina es obligatorio.',
            'costo_maquina_hora.numeric' => 'El costo de máquina debe ser un número.',
            'costo_maquina_hora.min' => 'El costo de máquina no puede ser negativo.',
            'costo_maquina_hora.decimal' => 'El costo de máquina admite hasta :decimal decimales.',
            'orden_catalogo.required' => 'El orden en el catálogo es obligatorio.',
            'orden_catalogo.integer' => 'El orden debe ser un número entero.',
            'orden_catalogo.min' => 'El orden empieza en :min.',
            'orden_catalogo.unique' => 'Otra área ya ocupa ese lugar en el catálogo.',
            'obligatoria.required' => 'Indica si el área es obligatoria.',
            'activo.required' => 'Indica si el área está activa.',
        ];
    }
}
