<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PublicarProductoRequest extends FormRequest
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
            'publicado' => ['required_without:mostrar_cantidad', 'boolean'],
            'mostrar_cantidad' => ['required_without:publicado', 'boolean'],
        ];
    }
}
