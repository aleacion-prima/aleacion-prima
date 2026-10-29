<?php

namespace Database\Seeders;

use App\Models\Area;
use Illuminate\Database\Seeder;

class AreaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $areas = [
            ['MOL', 'Moldeo y preparación de arenas', 180, 220, true, 'Mezcladora de arena verde, atacador, línea de cajas'],
            ['FUS', 'Fusión y ajuste metalúrgico', 220, 450, true, 'Horno de inducción 750 kg, báscula de carga'],
            ['COL', 'Colada y enfriamiento', 60, 80, true, 'Cuchara de colada, inoculación en cuchara'],
            ['DES', 'Desmolde', 160, 180, true, 'Desmolde vibratorio, separación de piezas y retornos'],
            ['GRA', 'Granallado y rebabeo', 170, 200, false, 'Granalladora de esfera de acero, esmeriles'],
            ['CAL', 'Laboratorio de calidad', 250, 300, false, 'Durómetro Brinell, espectrómetro, ultrasonido'],
            ['EMP', 'Empaque y expedición', 150, 40, false, 'Paletizado, etiquetado de contenedor'],
        ];

        foreach ($areas as $indice => [$codigo, $nombre, $manoObra, $maquina, $obligatoria, $equipo]) {
            Area::updateOrCreate(
                ['codigo' => $codigo],
                [
                    'nombre' => $nombre,
                    'descripcion' => $equipo,
                    'tarifa_mano_obra_hora' => $manoObra,
                    'costo_maquina_hora' => $maquina,
                    'obligatoria' => $obligatoria,
                    'orden_catalogo' => $indice + 1,
                    'activo' => true,
                ],
            );
        }
    }
}
