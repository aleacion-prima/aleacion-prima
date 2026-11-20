<?php

namespace App\Http\Requests;

use App\Models\User;
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

    /**
     * Comprueba las credenciales SIN iniciar la sesión y devuelve el usuario.
     * No inicia sesión aquí para poder ramificar al segundo factor: el login lo
     * cierra con Auth::login (sin 2FA) o lo hace SegundoFactorController al
     * verificar el código.
     */
    public function usuarioAutenticado(): User
    {
        $credenciales = [
            'email' => $this->string('email')->toString(),
            'password' => $this->string('password')->toString(),
            'activo' => true,
        ];

        if (! Auth::validate($credenciales)) {
            throw ValidationException::withMessages([
                'credenciales' => __('auth.failed'),
            ]);
        }

        $usuario = User::query()
            ->where('email', $this->string('email')->toString())
            ->where('activo', true)
            ->first();

        // Auth::validate ya confirmó que existe y está activo.
        abort_unless($usuario instanceof User, 401);

        return $usuario;
    }
}
