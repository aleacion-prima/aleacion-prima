<?php

namespace App\Http\Requests;

use App\Models\Area;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProductoRequest extends FormRequest
{
    /** El acceso lo controla `role:administrador` en la ruta (RF-07). */
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
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $producto = $this->route('producto');
        $precio = ['required', 'numeric', 'min:0', 'max:9999999999', 'decimal:0,4'];

        return [
            'codigo' => ['required', 'string', 'max:20', 'regex:/^[A-Z0-9]+(-[A-Z0-9]+)*$/', Rule::unique('productos', 'codigo')->ignore($producto)],
            'nombre' => ['required', 'string', 'max:255'],
            'especificaciones' => ['nullable', 'array'],
            'especificaciones.*' => [
                'nullable',
                fn (string $atributo, mixed $valor, Closure $fail) => is_scalar($valor) && mb_strlen((string) $valor) <= 255
                    ? null
                    : $fail('Cada especificación es un texto o un número de hasta 255 caracteres.'),
            ],
            'precio_mayoreo' => $precio,
            'precio_menudeo' => $precio,

            'recorrido' => [
                'required',
                'array',
                'min:1',
                fn (string $atributo, mixed $valor, Closure $fail) => $this->validarRecorrido($valor, $fail),
            ],
            'recorrido.*' => ['integer', 'distinct', Rule::exists('areas', 'id')],

            'receta' => ['required', 'array', 'min:1'],
            'receta.*.insumo_id' => ['required', 'integer', 'distinct', Rule::exists('insumos', 'id')->where('activo', true)],
            'receta.*.cantidad_por_pieza' => ['required', 'numeric', 'gt:0', 'max:99999999', 'decimal:0,4'],

            'activo' => $this->isMethod('POST') ? ['sometimes', 'boolean'] : ['required', 'boolean'],
        ];
    }

    /**
     * Recibe la lista de ids de área en el orden del recorrido.
     */
    private function validarRecorrido(mixed $valor, Closure $fail): void
    {
        // Si la forma es inválida, ya la reportan las reglas de arreglo y de enteros.
        if (! is_array($valor) || collect($valor)->contains(fn ($id) => ! is_numeric($id))) {
            return;
        }

        $ids = array_map('intval', array_values($valor));
        $areas = Area::query()->whereIn('id', $ids)->get()->keyBy('id');

        $inactivas = collect($ids)
            ->map(fn (int $id) => $areas->get($id))
            ->filter(fn (?Area $area) => $area !== null && ! $area->activo);

        if ($inactivas->isNotEmpty()) {
            $fail('Las áreas '.$inactivas->pluck('codigo')->implode(', ').' están inactivas y no pueden entrar en un recorrido nuevo.');

            return;
        }

        $obligatorias = Area::query()->activas()->where('obligatoria', true)->orderBy('orden_catalogo')->get();
        $enRecorrido = collect($ids)
            ->map(fn (int $id) => $areas->get($id))
            ->filter(fn (?Area $area) => $area !== null && $area->obligatoria)
            ->values();

        $faltantes = $obligatorias->pluck('codigo')->diff($enRecorrido->pluck('codigo'));
        if ($faltantes->isNotEmpty()) {
            $fail($faltantes->count() === 1
                ? 'Falta el área obligatoria '.$faltantes->first().'.'
                : 'Faltan las áreas obligatorias '.$faltantes->implode(', ').'.');

            return;
        }

        if ($enRecorrido->pluck('id')->all() !== $obligatorias->pluck('id')->all()) {
            $fail('Las áreas obligatorias van en este orden: '.$obligatorias->pluck('codigo')->implode(' → ').'.');
        }
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'codigo.required' => 'El código es obligatorio.',
            'codigo.regex' => 'El código lleva letras mayúsculas, números y guiones, por ejemplo DIS-FRE-G.',
            'codigo.unique' => 'Ya existe un producto con ese código.',
            'nombre.required' => 'El nombre es obligatorio.',
            'precio_mayoreo.required' => 'El precio de mayoreo es obligatorio.',
            'precio_mayoreo.min' => 'El precio de mayoreo no puede ser negativo.',
            'precio_mayoreo.decimal' => 'El precio de mayoreo admite hasta :decimal decimales.',
            'precio_menudeo.required' => 'El precio de menudeo es obligatorio.',
            'precio_menudeo.min' => 'El precio de menudeo no puede ser negativo.',
            'precio_menudeo.decimal' => 'El precio de menudeo admite hasta :decimal decimales.',
            'recorrido.required' => 'El recorrido por defecto es obligatorio.',
            'recorrido.min' => 'El recorrido necesita al menos un área.',
            'recorrido.*.distinct' => 'No repitas un área en el recorrido por defecto.',
            'recorrido.*.exists' => 'Una de las áreas del recorrido no existe.',
            'receta.required' => 'La receta es obligatoria: sin ella no se pueden asignar insumos a un lote.',
            'receta.min' => 'La receta necesita al menos un insumo.',
            'receta.*.insumo_id.required' => 'Elige el insumo de cada renglón de la receta.',
            'receta.*.insumo_id.distinct' => 'No repitas un insumo en la receta.',
            'receta.*.insumo_id.exists' => 'Un insumo de la receta no existe o está inactivo.',
            'receta.*.cantidad_por_pieza.required' => 'Indica la cantidad por pieza de cada insumo.',
            'receta.*.cantidad_por_pieza.gt' => 'La cantidad por pieza debe ser mayor que cero.',
            'receta.*.cantidad_por_pieza.decimal' => 'La cantidad por pieza admite hasta :decimal decimales.',
            'activo.required' => 'Indica si el producto está activo.',
        ];
    }
}
