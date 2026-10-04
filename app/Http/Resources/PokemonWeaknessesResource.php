<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Domain\Combat\PokemonType;
use App\Domain\Combat\TypeChart;
use App\Models\Pokemon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Pokemon
 */
class PokemonWeaknessesResource extends JsonResource
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
            'weaknesses' => array_map(
                fn (PokemonType $type): string => $type->value,
                TypeChart::weaknessesOf($this->type),
            ),
        ];
    }
}
