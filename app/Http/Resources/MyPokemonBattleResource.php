<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Battle;
use App\Models\MyPokemon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Un combate del historial de un MyPokemon, visto desde su propio punto de vista.
 *
 * @mixin Battle
 */
class MyPokemonBattleResource extends JsonResource
{
    private int $viewpointId;

    /**
     * Fija el MyPokemon desde cuyo punto de vista se calculan el rival y el resultado.
     */
    public function forMyPokemon(int $viewpointId): self
    {
        $this->viewpointId = $viewpointId;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $rival = $this->rival();

        return [
            'id' => $this->id,
            'rival' => [
                'id' => $rival->id,
                'nickname' => $rival->nickname,
                'pokemon' => new PokemonResource($rival->pokemon),
            ],
            'result' => $this->result(),
            'created_at' => $this->created_at->toISOString(),
            'turn_number' => $this->turn_number,
        ];
    }

    private function rival(): MyPokemon
    {
        return $this->first_my_pokemon_id === $this->viewpointId
            ? $this->secondMyPokemon
            : $this->firstMyPokemon;
    }

    private function result(): string
    {
        if (! $this->isFinished()) {
            return 'in_progress';
        }

        return $this->winner_my_pokemon_id === $this->viewpointId ? 'won' : 'lost';
    }
}
