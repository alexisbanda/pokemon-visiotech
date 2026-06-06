<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BattleSide;
use App\Enums\BattleStatus;
use Database\Factories\BattleFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Estado de un combate por turnos entre dos MyPokemon.
 *
 * @property-read MyPokemon $firstMyPokemon
 * @property-read MyPokemon $secondMyPokemon
 * @property-read Collection<int, BattleTurn> $turns
 */
class Battle extends Model
{
    /** @use HasFactory<BattleFactory> */
    use HasFactory;

    protected $fillable = [
        'first_my_pokemon_id', 'second_my_pokemon_id',
        'first_current_hp', 'second_current_hp',
        'turn', 'status', 'turn_number', 'winner_my_pokemon_id',
    ];

    protected function casts(): array
    {
        return [
            'turn' => BattleSide::class,
            'status' => BattleStatus::class,
            'first_current_hp' => 'integer',
            'second_current_hp' => 'integer',
            'turn_number' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<MyPokemon, $this>
     */
    public function firstMyPokemon(): BelongsTo
    {
        return $this->belongsTo(MyPokemon::class, 'first_my_pokemon_id');
    }

    /**
     * @return BelongsTo<MyPokemon, $this>
     */
    public function secondMyPokemon(): BelongsTo
    {
        return $this->belongsTo(MyPokemon::class, 'second_my_pokemon_id');
    }

    /**
     * @return BelongsTo<MyPokemon, $this>
     */
    public function winner(): BelongsTo
    {
        return $this->belongsTo(MyPokemon::class, 'winner_my_pokemon_id');
    }

    /**
     * @return HasMany<BattleTurn, $this>
     */
    public function turns(): HasMany
    {
        return $this->hasMany(BattleTurn::class)->orderBy('number');
    }

    public function isFinished(): bool
    {
        return $this->status === BattleStatus::Finished;
    }

    /**
     * El MyPokemon que ocupa un lado del combate.
     */
    public function combatantOn(BattleSide $side): MyPokemon
    {
        return $side === BattleSide::First ? $this->firstMyPokemon : $this->secondMyPokemon;
    }

    public function currentHp(BattleSide $side): int
    {
        return $side === BattleSide::First ? $this->first_current_hp : $this->second_current_hp;
    }

    public function setCurrentHp(BattleSide $side, int $hp): void
    {
        $hp = max(0, $hp);

        if ($side === BattleSide::First) {
            $this->first_current_hp = $hp;
        } else {
            $this->second_current_hp = $hp;
        }
    }
}
