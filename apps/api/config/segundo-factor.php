<?php

return [
    // Minutos que vive un código de acceso antes de vencer.
    'vigencia_minutos' => 10,

    // Intentos fallidos permitidos por código antes de pedir uno nuevo.
    'max_intentos' => 5,

    // El administrador debe usar segundo factor obligatoriamente. En desarrollo
    // y en la prueba E2E se apaga con SEGUNDO_FACTOR_ADMINISTRADOR=false.
    'obligatorio_administrador' => env('SEGUNDO_FACTOR_ADMINISTRADOR', true),
];
