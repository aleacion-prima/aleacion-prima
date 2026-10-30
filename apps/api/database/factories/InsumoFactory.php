<?php

namespace Database\Factories;

use App\Models\Insumo;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Insumo> */
class InsumoFactory extends Factory
{
    protected $model = Insumo::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'codigo' => strtoupper(fake()->unique()->bothify('???-###')),
            'nombre' => fake()->unique()->words(3, true),
            'categoria' => fake()->randomElement(Insumo::CATEGORIAS),
            'unidad' => fake()->randomElement(['kg', 'pieza']),
            'costo_unitario' => fake()->randomFloat(4, 1, 100),
            'stock_minimo' => fake()->randomFloat(3, 0, 500),
            'existencia' => 0,
            'activo' => true,
        ];
    }

    public function inactivo(): static
    {
        return $this->state(fn () => ['activo' => false]);
    }
}
