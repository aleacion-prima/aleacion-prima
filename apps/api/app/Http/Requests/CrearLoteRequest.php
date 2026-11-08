<?php

namespace App\Http\Requests;

use App\Recorridos\ValidadorRecorrido;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CrearLoteRequest extends FormRequest
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
            'producto_id' => ['required', 'integer', Rule::exists('productos', 'id')->where('activo', true)],
            'cantidad_objetivo' => ['required', 'integer', 'min:1', 'max:9999'],
            'recorrido' => [
                'sometimes',
                'array',
                'min:1',
                fn (string $atributo, mixed $valor, Closure $fail) => $this->validarRecorrido($valor, $fail),
            ],
            'recorrido.*' => ['integer', Rule::exists('areas', 'id')],
        ];
    }

    private function validarRecorrido(mixed $valor, Closure $fail): void
    {
        if (! is_array($valor) || collect($valor)->contains(fn ($id) => ! is_numeric($id))) {
            return;
        }

        $error = app(ValidadorRecorrido::class)->error(
            array_map('intval', array_values($valor)),
            permitirRepetir: true,
        );

        if ($error !== null) {
            $fail($error);
        }
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'producto_id.required' => 'Elige el producto a fabricar.',
            'producto_id.exists' => 'El producto no existe o está inactivo.',
            'cantidad_objetivo.required' => 'Indica cuántas piezas lleva el lote.',
            'cantidad_objetivo.integer' => 'La cantidad de piezas debe ser un número entero.',
            'cantidad_objetivo.min' => 'El lote debe tener al menos una pieza.',
            'cantidad_objetivo.max' => 'La cantidad de piezas no puede pasar de :max.',
            'recorrido.array' => 'El recorrido debe ser una lista de áreas.',
            'recorrido.min' => 'El recorrido necesita al menos un área.',
            'recorrido.*.exists' => 'Una de las áreas del recorrido no existe.',
        ];
    }
}
