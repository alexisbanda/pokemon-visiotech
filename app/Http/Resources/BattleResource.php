<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Enums\BattleSide;
use App\Models\Battle;
use App\Models\MyPokemon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Battle
 */
class BattleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status->value,
            'turn_number' => $this->turn_number,
            'turn' => $this->isFinished() ? null : $this->turn->value,
            'combatants' => [
                'first' => $this->combatant(BattleSide::First),
                'second' => $this->combatant(BattleSide::Second),
            ],
            'winner_my_pokemon_id' => $this->winner_my_pokemon_id,
            'turns' => BattleTurnResource::collection($this->whenLoaded('turns')),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function combatant(BattleSide $side): array
    {
        /** @var MyPokemon $myPokemon */
        $myPokemon = $this->combatantOn($side);

        return [
            'side' => $side->value,
            'my_pokemon_id' => $myPokemon->id,
            'nickname' => $myPokemon->nickname,
            'species' => $myPokemon->pokemon->name,
            'type' => $myPokemon->pokemon->type->value,
            'level' => $myPokemon->level,
            'max_hp' => $myPokemon->pokemon->hp,
            'current_hp' => $this->currentHp($side),
            'fainted' => $this->currentHp($side) <= 0,
        ];
    }
}
