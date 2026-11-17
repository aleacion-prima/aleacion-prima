<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ResolverImprevistoRequest extends FormRequest
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
            'resuelto' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'resuelto.required' => 'Indica si el imprevisto quedó resuelto.',
        ];
    }
}
