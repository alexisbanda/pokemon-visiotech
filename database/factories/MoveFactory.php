<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Combat\PokemonType;
use App\Models\Move;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Move>
 */
class MoveFactory extends Factory
{
    protected $model = Move::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'power' => fake()->numberBetween(40, 120),
            'type' => fake()->randomElement(PokemonType::cases()),
        ];
    }
}
