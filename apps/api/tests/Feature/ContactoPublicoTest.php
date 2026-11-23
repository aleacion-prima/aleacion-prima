<?php

use function Pest\Laravel\getJson;

it('devuelve el contacto de ventas configurado sin datos privados', function () {
    config()->set('contacto', [
        'correo' => 'ventas@aleacionprima.com',
        'telefono' => '+52 871 000 0000',
        'horario' => 'Lunes a viernes, 07:00 a 17:00',
    ]);

    $respuesta = getJson('/api/publico/contacto')
        ->assertOk()
        ->assertExactJson([
            'correo' => 'ventas@aleacionprima.com',
            'telefono' => '+52 871 000 0000',
            'horario' => 'Lunes a viernes, 07:00 a 17:00',
        ]);

    $claves = collect($respuesta->json())
        ->keys()
        ->filter(fn (string $clave) => preg_match('/precio|costo|tarifa/i', $clave));

    expect($claves)->toBeEmpty();
});
