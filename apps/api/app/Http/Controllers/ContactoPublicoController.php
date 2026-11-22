<?php

namespace App\Http\Controllers;

use App\Http\Resources\ContactoPublicoResource;
use Illuminate\Http\Resources\Json\JsonResource;

class ContactoPublicoController extends Controller
{
    public function __invoke(): JsonResource
    {
        return new ContactoPublicoResource(config('contacto'));
    }
}
