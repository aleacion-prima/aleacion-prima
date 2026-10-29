<?php

namespace Database\Factories;

use App\Models\Producto;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Producto>
 */
class ProductoFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'codigo' => strtoupper(fake()->unique()->lexify('???-???-?')),
            'nombre' => fake()->unique()->words(3, true),
            'especificaciones' => ['material' => 'Hierro gris clase 30', 'peso_kg' => 5],
            'precio_mayoreo' => fake()->randomFloat(2, 100, 500),
            'precio_menudeo' => fake()->randomFloat(2, 150, 700),
            'publicado' => false,
            'activo' => true,
        ];
    }

    public function inactivo(): static
    {
        return $this->state(['activo' => false]);
    }
}
