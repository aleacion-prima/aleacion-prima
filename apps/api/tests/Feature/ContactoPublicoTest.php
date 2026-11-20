<?php

use function Pest\Laravel\getJson;

it('devuelve los datos de contacto configurados sin precios', function () {
    config()->set('contacto', [
        'correo' => 'ventas.demo@aleacionprima.example',
        'telefono' => '+52 55 0000 0000',
        'horario' => 'Lunes a viernes, de 9:00 a 17:00',
    ]);

    getJson('/api/publico/contacto')
        ->assertOk()
        ->assertJson([
            'correo' => 'ventas.demo@aleacionprima.example',
            'telefono' => '+52 55 0000 0000',
            'horario' => 'Lunes a viernes, de 9:00 a 17:00',
        ])
        ->assertJsonMissingPath('precio_mayoreo')
        ->assertJsonMissingPath('precio_menudeo');
});
