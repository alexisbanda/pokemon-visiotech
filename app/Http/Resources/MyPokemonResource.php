<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\MyPokemon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin MyPokemon
 */
class MyPokemonResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nickname' => $this->nickname,
            'level' => $this->level,
            'pokemon' => new PokemonResource($this->whenLoaded('pokemon')),
            'moves' => MoveResource::collection($this->whenLoaded('moves')),
        ];
    }
}
