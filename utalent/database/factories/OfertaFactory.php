<?php

namespace Database\Factories;

use App\Models\Fuente;
use App\Models\Oferta;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Oferta>
 */
class OfertaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'fuente_id' => Fuente::factory(),
            'external_id' => $this->faker->unique()->numerify('####'),
            'titulo' => $this->faker->jobTitle(),
            'empresa' => $this->faker->company(),
            'departamento' => $this->faker->randomElement(['Montevideo', 'Canelones', 'Rocha']),
            'es_publico' => $this->faker->boolean(),
            'salario_visible' => $this->faker->boolean(),
            'salario_texto' => null,
            'modalidad' => $this->faker->randomElement(['Online', 'Presencial']),
            'url' => $this->faker->url(),
            'fecha_publicacion' => now()->subDays($this->faker->numberBetween(0, 30)),
            'fecha_cierre' => null,
            'estado' => 'activa',
            'descripcion_cruda' => null,
        ];
    }
}
