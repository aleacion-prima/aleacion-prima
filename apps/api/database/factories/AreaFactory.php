<?php

namespace Database\Factories;

use App\Models\Area;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Area>
 */
class AreaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'codigo' => strtoupper(fake()->unique()->lexify('???')),
            'nombre' => fake()->unique()->words(3, true),
            'descripcion' => fake()->optional()->sentence(),
            'tarifa_mano_obra_hora' => fake()->randomFloat(2, 50, 300),
            'costo_maquina_hora' => fake()->randomFloat(2, 30, 500),
            'obligatoria' => false,
            // Lejos de 1–7, que ocupa el seeder.
            'orden_catalogo' => fake()->unique()->numberBetween(100, 9999),
            'activo' => true,
        ];
    }

    public function inactiva(): static
    {
        return $this->state(['activo' => false]);
    }

    public function obligatoria(): static
    {
        return $this->state(['obligatoria' => true]);
    }
}
