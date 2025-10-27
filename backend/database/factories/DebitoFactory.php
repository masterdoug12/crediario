<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Debito>
 */
class DebitoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'descricao' => $this->faker->sentence(4),
            'tipo' => $this->faker->optional()->randomElement(['Mercadorias', 'Serviço', 'Taxa']),
            'valor' => $this->faker->randomFloat(2, 20, 500),
            'data' => $this->faker->dateTimeBetween('-3 months', 'now')->format('Y-m-d'),
        ];
    }
}
