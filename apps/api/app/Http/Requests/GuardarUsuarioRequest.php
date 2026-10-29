<?php

namespace App\Http\Requests;

use App\Enums\Rol;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GuardarUsuarioRequest extends FormRequest
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
        $usuario = $this->route('usuario');
        $usuarioId = $usuario instanceof User ? $usuario->id : null;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($usuarioId),
            ],
            'password' => [$this->isMethod('POST') ? 'required' : 'nullable', 'string', 'min:8'],
            'rol' => ['required', Rule::enum(Rol::class)],
            'activo' => ['sometimes', 'boolean'],
            'area_ids' => [
                'nullable',
                'array',
                Rule::requiredIf(fn () => $this->string('rol')->toString() === Rol::Encargado->value),
                Rule::when($this->string('rol')->toString() === Rol::Encargado->value, ['min:1']),
            ],
            'area_ids.*' => ['integer', 'distinct', 'exists:areas,id'],
        ];
    }
}
