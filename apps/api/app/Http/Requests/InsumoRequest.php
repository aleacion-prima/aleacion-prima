<?php

namespace App\Http\Requests;

use App\Models\Insumo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InsumoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('codigo'))) {
            $this->merge(['codigo' => strtoupper(trim($this->input('codigo')))]);
        }
    }

    private function insumoDeRuta(): ?Insumo
    {
        $insumo = $this->route('insumo');

        return $insumo instanceof Insumo ? $insumo : null;
    }

    private function esRetorno(): bool
    {
        return $this->insumoDeRuta()?->codigo === (string) config('planta.insumo_retorno');
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        $insumo = $this->insumoDeRuta();
        $codigoRetorno = (string) config('planta.insumo_retorno');
        $esRetorno = $this->esRetorno();

        $codigo = [
            'required',
            'string',
            'max:20',
            'regex:/^[A-Z0-9]+(-[A-Z0-9]+)*$/',
            Rule::unique('insumos', 'codigo')->ignore($insumo),
        ];
        $activo = ['sometimes', 'boolean'];

        if ($esRetorno) {
            $codigo[] = Rule::in([$codigoRetorno]);
            $activo[] = 'accepted';
        }

        return [
            'codigo' => $codigo,
            'nombre' => ['required', 'string', 'max:120', Rule::unique('insumos', 'nombre')->ignore($insumo)],
            'categoria' => ['required', Rule::in($esRetorno ? ['metal'] : Insumo::CATEGORIAS)],
            'unidad' => ['required', 'string', 'max:20'],
            'costo_unitario' => ['required', 'numeric', 'min:0', 'max:9999999999', 'decimal:0,4'],
            'stock_minimo' => ['required', 'numeric', 'min:0', 'max:999999999', 'decimal:0,3'],
            'activo' => $activo,
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'codigo.regex' => 'El código solo admite mayúsculas, números y guiones (por ejemplo, CHT-FUN).',
            'codigo.in' => 'El código del insumo de retorno no se puede cambiar.',
            'categoria.in' => $this->esRetorno()
                ? 'El insumo de retorno debe seguir siendo de categoría metal.'
                : 'La categoría debe ser metal, moldeo o empaque.',
            'activo.accepted' => 'El insumo de retorno no se puede desactivar.',
            'costo_unitario.decimal' => 'El costo unitario admite hasta 4 decimales.',
            'stock_minimo.decimal' => 'El stock mínimo admite hasta 3 decimales.',
        ];
    }
}
