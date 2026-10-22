<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
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
            'email' => ['required', 'email'],
            'password' => ['required'],
        ];
    }

    public function autenticar(): void
    {
        $credenciales = [
            'email' => $this->string('email')->toString(),
            'password' => $this->string('password')->toString(),
            'activo' => true,
        ];

        if (! Auth::attempt($credenciales, $this->boolean('recordar'))) {
            throw ValidationException::withMessages([
                'credenciales' => __('auth.failed'),
            ]);
        }
    }
}
