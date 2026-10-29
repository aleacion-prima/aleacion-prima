<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\Insumo;
use App\Models\Producto;
use Illuminate\Database\Seeder;
use RuntimeException;

class ProductoSeeder extends Seeder
{
    private const RECORRIDO_COMPLETO = ['MOL', 'FUS', 'COL', 'DES', 'GRA', 'CAL'];

    public function run(): void
    {
        foreach ($this->productos() as $datos) {
            $producto = Producto::updateOrCreate(
                ['codigo' => $datos['codigo']],
                [
                    'nombre' => $datos['nombre'],
                    'especificaciones' => $datos['especificaciones'],
                    'precio_mayoreo' => $datos['mayoreo'],
                    'precio_menudeo' => $datos['menudeo'],
                    'publicado' => true,
                    'activo' => true,
                ],
            );

            $producto->areas()->detach();
            foreach ($datos['recorrido'] as $indice => $codigoArea) {
                $producto->areas()->attach($this->area($codigoArea)->id, ['orden' => $indice + 1]);
            }

            $producto->insumos()->sync(
                collect($datos['receta'])
                    ->mapWithKeys(fn (float $kg, string $codigo) => [
                        $this->insumo($codigo)->id => ['cantidad_por_pieza' => $kg],
                    ])->all()
            );
        }
    }

    private function area(string $codigo): Area
    {
        return Area::where('codigo', $codigo)->first()
            ?? throw new RuntimeException("Falta el área {$codigo}: corre AreaSeeder antes de ProductoSeeder.");
    }

    private function insumo(string $codigo): Insumo
    {
        return Insumo::where('codigo', $codigo)->first()
            ?? throw new RuntimeException("Falta el insumo {$codigo}: siembra los insumos antes de ProductoSeeder.");
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function productos(): array
    {
        return [
            [
                'codigo' => 'DIS-FRE-G',
                'nombre' => 'Disco de freno',
                'especificaciones' => [
                    'material' => 'Hierro gris clase 30',
                    'norma' => 'ASTM A48',
                    'peso_kg' => 8.5,
                    'diametro_mm' => 300,
                    'notas' => 'En bruto, para maquinado por el cliente',
                ],
                'mayoreo' => 280,
                'menudeo' => 340,
                'recorrido' => self::RECORRIDO_COMPLETO,
                'receta' => ['CHT-FUN' => 5.57, 'CHT-102' => 5.57, 'REC-GRA' => 0.29, 'FES-75' => 0.17, 'ARE-VER' => 12.00],
            ],
            [
                'codigo' => 'POL-IND-G',
                'nombre' => 'Polea industrial',
                'especificaciones' => [
                    'material' => 'Hierro gris clase 30',
                    'norma' => 'ASTM A48',
                    'peso_kg' => 4.2,
                    'diametro_mm' => 250,
                    'notas' => '2 canales tipo B',
                ],
                'mayoreo' => 170,
                'menudeo' => 210,
                'recorrido' => self::RECORRIDO_COMPLETO,
                'receta' => ['CHT-FUN' => 2.74, 'CHT-102' => 2.74, 'REC-GRA' => 0.14, 'FES-75' => 0.08, 'ARE-VER' => 6.00],
            ],
            [
                'codigo' => 'CAJ-TRA-N',
                'nombre' => 'Caja de transmisión',
                'especificaciones' => [
                    'material' => 'Hierro nodular 65-45-12',
                    'norma' => 'ASTM A536',
                    'peso_kg' => 14,
                    'notas' => 'Con núcleo de arena resina',
                ],
                'mayoreo' => 650,
                'menudeo' => 780,
                'recorrido' => self::RECORRIDO_COMPLETO,
                'receta' => ['CHT-FUN' => 9.12, 'CHT-102' => 9.12, 'REC-GRA' => 0.48, 'FES-75' => 0.28, 'NOD-FSM' => 0.38, 'ARE-VER' => 20.00],
            ],
            [
                'codigo' => 'CAR-PRE-N',
                'nombre' => 'Carcasa de precisión',
                'especificaciones' => [
                    'material' => 'Hierro nodular 65-45-12',
                    'norma' => 'ASTM A536',
                    'peso_kg' => 6.5,
                    'notas' => 'Tolerancias cerradas en asientos de rodamiento; no se granalla para no dañar el acabado antes del ultrasonido',
                ],
                'mayoreo' => 420,
                'menudeo' => 500,
                'recorrido' => ['MOL', 'FUS', 'COL', 'DES', 'CAL'],
                'receta' => ['CHT-FUN' => 4.22, 'CHT-102' => 4.22, 'REC-GRA' => 0.22, 'FES-75' => 0.14, 'NOD-FSM' => 0.18, 'ARE-VER' => 10.00],
            ],
            [
                'codigo' => 'SOP-MAN-N',
                'nombre' => 'Soporte de mangueta agrícola',
                'especificaciones' => [
                    'material' => 'Hierro nodular 80-55-06',
                    'norma' => 'ASTM A536',
                    'peso_kg' => 11.5,
                    'notas' => 'Servicio pesado para tractor',
                ],
                'mayoreo' => 540,
                'menudeo' => 650,
                'recorrido' => self::RECORRIDO_COMPLETO,
                'receta' => ['CHT-FUN' => 7.49, 'CHT-102' => 7.49, 'REC-GRA' => 0.39, 'FES-75' => 0.23, 'NOD-FSM' => 0.31, 'ARE-VER' => 16.00],
            ],
        ];
    }
}
