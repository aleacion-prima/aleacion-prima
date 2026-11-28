<?php

declare(strict_types=1);

namespace App\Enums;

enum AccionBitacora: string
{
    case Crear = 'crear';
    case Actualizar = 'actualizar';
    case Eliminar = 'eliminar';
    case Login = 'login';
    case Logout = 'logout';
    case CerrarSesiones = 'cerrar_sesiones';
    case CambiarModulo = 'cambiar_modulo';
}
