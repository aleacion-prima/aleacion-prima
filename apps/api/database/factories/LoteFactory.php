<?php

namespace Database\Factories;

use App\Enums\EstadoLote;
use App\Models\Lote;
use App\Models\Producto;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lote>
 */
class LoteFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'codigo' => 'LOT-'.now()->format('Ymd').'-'.str_pad((string) fake()->unique()->numberBetween(1, 99), 2, '0', STR_PAD_LEFT),
            'producto_id' => Producto::factory(),
            'cantidad_objetivo' => 20,
            'estado' => EstadoLote::Creado,
            'creado_por' => User::factory(),
        ];
    }

    public function enProceso(): static
    {
        return $this->state(['estado' => EstadoLote::EnProceso]);
    }
}
