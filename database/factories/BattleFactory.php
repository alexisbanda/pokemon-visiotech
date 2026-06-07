<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\BattleSide;
use App\Enums\BattleStatus;
use App\Models\Battle;
use App\Models\MyPokemon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Battle>
 */
class BattleFactory extends Factory
{
    protected $model = Battle::class;

    public function definition(): array
    {
        $first = MyPokemon::factory()->create();
        $second = MyPokemon::factory()->create();

        return [
            'first_my_pokemon_id' => $first->id,
            'second_my_pokemon_id' => $second->id,
            'first_current_hp' => $first->maxHp(),
            'second_current_hp' => $second->maxHp(),
            'turn' => BattleSide::First,
            'status' => BattleStatus::InProgress,
            'turn_number' => 0,
            'winner_my_pokemon_id' => null,
        ];
    }
}
