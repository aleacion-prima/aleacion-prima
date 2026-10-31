<?php

namespace Database\Factories;

use App\Models\Insumo;
use App\Models\Lote;
use App\Models\LoteInsumo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LoteInsumo>
 */
class LoteInsumoFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'lote_id' => Lote::factory(),
            'insumo_id' => Insumo::factory(),
            'cantidad' => 100,
            'costo_unitario' => 7,
        ];
    }
}
