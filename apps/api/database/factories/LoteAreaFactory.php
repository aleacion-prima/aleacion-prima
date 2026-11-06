<?php

namespace Database\Factories;

use App\Models\Area;
use App\Models\Lote;
use App\Models\LoteArea;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LoteArea>
 */
class LoteAreaFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'lote_id' => Lote::factory(),
            'area_id' => Area::factory(),
            'orden' => 1,
            'tarifa_mano_obra_hora' => 180,
            'costo_maquina_hora' => 220,
        ];
    }
}
