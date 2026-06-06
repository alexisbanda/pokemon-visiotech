<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Move;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Move
 */
class MoveResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'power' => $this->power,
            'type' => $this->type->value,
        ];
    }
}
