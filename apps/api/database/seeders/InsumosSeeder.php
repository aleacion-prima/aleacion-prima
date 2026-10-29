<?php

namespace Database\Seeders;

use App\Enums\CategoriaInsumo;
use App\Enums\TipoMovimientoInsumo;
use App\Models\Insumo;
use App\Models\User;
use Illuminate\Database\Seeder;

class InsumosSeeder extends Seeder
{
    public function run(): void
    {
        $administrador = User::query()->where('email', 'admin@aleacionprima.test')->firstOrFail();
        $ocurridoEn = '2026-09-21 08:00:00';

        $insumos = [
            ['codigo' => 'CHT-FUN', 'nombre' => 'Chatarra de fundición seleccionada', 'categoria' => CategoriaInsumo::Metal, 'unidad' => 'kg', 'costo_unitario' => 7, 'stock_minimo' => 5000, 'cantidad' => 12000, 'proveedor' => 'Recicladora Laguna', 'lote_proveedor' => null],
            ['codigo' => 'CHT-102', 'nombre' => 'Chatarra de acero SAE 1020', 'categoria' => CategoriaInsumo::Metal, 'unidad' => 'kg', 'costo_unitario' => 9.5, 'stock_minimo' => 4000, 'cantidad' => 9000, 'proveedor' => 'Aceros del Norte', 'lote_proveedor' => 'H-26-0412'],
            ['codigo' => 'REC-GRA', 'nombre' => 'Recarburante de grafito', 'categoria' => CategoriaInsumo::Metal, 'unidad' => 'kg', 'costo_unitario' => 30, 'stock_minimo' => 150, 'cantidad' => 600, 'proveedor' => 'Minerales Industriales MX', 'lote_proveedor' => 'RG-2607'],
            ['codigo' => 'FES-75', 'nombre' => 'Ferrosilicio FeSi 75', 'categoria' => CategoriaInsumo::Metal, 'unidad' => 'kg', 'costo_unitario' => 40, 'stock_minimo' => 150, 'cantidad' => 400, 'proveedor' => 'Ferroaleaciones de Monterrey', 'lote_proveedor' => 'FS-26-118'],
            ['codigo' => 'NOD-FSM', 'nombre' => 'Nodulizante FeSiMg 5 %', 'categoria' => CategoriaInsumo::Metal, 'unidad' => 'kg', 'costo_unitario' => 80, 'stock_minimo' => 100, 'cantidad' => 250, 'proveedor' => 'Ferroaleaciones de Monterrey', 'lote_proveedor' => 'NM-26-031'],
            ['codigo' => 'ARE-VER', 'nombre' => 'Arena verde (mezcla)', 'categoria' => CategoriaInsumo::Moldeo, 'unidad' => 'kg', 'costo_unitario' => 1.2, 'stock_minimo' => 10000, 'cantidad' => 25000, 'proveedor' => 'Preparación interna', 'lote_proveedor' => null],
            ['codigo' => 'CAJ-EMP', 'nombre' => 'Caja de despacho', 'categoria' => CategoriaInsumo::Empaque, 'unidad' => 'pieza', 'costo_unitario' => 45, 'stock_minimo' => 100, 'cantidad' => 300, 'proveedor' => 'Empaques Torreón', 'lote_proveedor' => null],
        ];

        foreach ($insumos as $datos) {
            $insumo = Insumo::query()->updateOrCreate(
                ['codigo' => $datos['codigo']],
                [
                    'nombre' => $datos['nombre'],
                    'categoria' => $datos['categoria']->value,
                    'unidad' => $datos['unidad'],
                    'costo_unitario' => $datos['costo_unitario'],
                    'stock_minimo' => $datos['stock_minimo'],
                    'activo' => true,
                ],
            );

            if (! $insumo->movimientos()->where('tipo', TipoMovimientoInsumo::Entrada)->exists()) {
                $insumo->movimientos()->create([
                    'tipo' => TipoMovimientoInsumo::Entrada,
                    'cantidad' => $datos['cantidad'],
                    'costo_unitario' => $datos['costo_unitario'],
                    'proveedor' => $datos['proveedor'],
                    'lote_proveedor' => $datos['lote_proveedor'],
                    'user_id' => $administrador->id,
                    'ocurrido_en' => $ocurridoEn,
                ]);
            }

            $insumo->update([
                'existencia' => $insumo->movimientos()->sum('cantidad'),
            ]);
        }
    }
}
