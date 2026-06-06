<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BattleSide;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Una fase registrada del combate (atacante, movimiento, daño, resultado).
 *
 * @property-read Move $move
 */
class BattleTurn extends Model
{
    protected $fillable = [
        'battle_id', 'number', 'attacker', 'move_id',
        'damage', 'effectiveness', 'label', 'defender_hp_after',
    ];

    protected function casts(): array
    {
        return [
            'attacker' => BattleSide::class,
            'number' => 'integer',
            'damage' => 'integer',
            'effectiveness' => 'float',
            'defender_hp_after' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Battle, $this>
     */
    public function battle(): BelongsTo
    {
        return $this->belongsTo(Battle::class);
    }

    /**
     * @return BelongsTo<Move, $this>
     */
    public function move(): BelongsTo
    {
        return $this->belongsTo(Move::class);
    }
}
