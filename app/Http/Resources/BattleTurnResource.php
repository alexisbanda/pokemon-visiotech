<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\BattleTurn;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin BattleTurn
 */
class BattleTurnResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'number' => $this->number,
            'attacker' => $this->attacker->value,
            'move' => MoveResource::make($this->whenLoaded('move')),
            'damage' => $this->damage,
            'effectiveness' => $this->effectiveness,
            'label' => $this->label,
            'defender_hp_after' => $this->defender_hp_after,
        ];
    }
}
