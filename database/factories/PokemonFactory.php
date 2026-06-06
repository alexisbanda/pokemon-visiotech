<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Combat\PokemonType;
use App\Models\Pokemon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Pokemon>
 */
class PokemonFactory extends Factory
{
    protected $model = Pokemon::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->firstName(),
            'type' => fake()->randomElement(PokemonType::cases()),
            'hp' => fake()->numberBetween(35, 130),
            'attack' => fake()->numberBetween(35, 130),
            'defense' => fake()->numberBetween(35, 130),
            'sp_attack' => fake()->numberBetween(35, 130),
            'sp_defense' => fake()->numberBetween(35, 130),
            'speed' => fake()->numberBetween(35, 130),
        ];
    }
}
