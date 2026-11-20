<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

class ContactoPublicoController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json(config('contacto'));
    }
}
