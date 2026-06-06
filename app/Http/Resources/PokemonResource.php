<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Pokemon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Pokemon
 */
class PokemonResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type->value,
            'stats' => [
                'hp' => $this->hp,
                'attack' => $this->attack,
                'defense' => $this->defense,
                'sp_attack' => $this->sp_attack,
                'sp_defense' => $this->sp_defense,
                'speed' => $this->speed,
            ],
            'moves' => MoveResource::collection($this->whenLoaded('moves')),
        ];
    }
}
