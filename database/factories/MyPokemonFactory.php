<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\MyPokemon;
use App\Models\Pokemon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MyPokemon>
 */
class MyPokemonFactory extends Factory
{
    protected $model = MyPokemon::class;

    public function definition(): array
    {
        return [
            'pokemon_id' => Pokemon::factory(),
            'nickname' => fake()->firstName(),
            'level' => fake()->numberBetween(1, 100),
        ];
    }
}
