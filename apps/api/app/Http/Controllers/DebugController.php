<?php

namespace App\Http\Controllers;

use App\Http\Resources\UsuarioResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;

class DebugController extends Controller
{
    public function usuarios(): AnonymousResourceCollection
    {
        return UsuarioResource::collection(
            User::query()->where('activo', true)
                ->with('areas')
                ->orderBy('name')
                ->get()
        );
    }

    public function entrarComo(Request $request, User $user): UsuarioResource
    {
        abort_unless($user->activo, 422, 'El usuario está desactivado.');

        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        return new UsuarioResource($user->load('areas'));
    }
}
