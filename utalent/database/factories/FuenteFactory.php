<?php

namespace Database\Factories;

use App\Models\Fuente;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Fuente>
 */
class FuenteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => $this->faker->unique()->slug(2, false),
            'nombre_visible' => $this->faker->company(),
            'tipo' => $this->faker->randomElement(['publico', 'privado']),
            'activa' => true,
        ];
    }
}
