<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Combat\PokemonType;
use Database\Factories\PokemonFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Pokémon base de la Pokédex: stats base + tipo + movimientos que puede aprender.
 *
 * @property-read Collection<int, Move> $moves
 */
class Pokemon extends Model
{
    /** @use HasFactory<PokemonFactory> */
    use HasFactory;

    protected $table = 'pokemon';

    protected $fillable = [
        'name', 'type', 'hp', 'attack', 'defense', 'sp_attack', 'sp_defense', 'speed',
    ];

    protected function casts(): array
    {
        return [
            'type' => PokemonType::class,
            'hp' => 'integer',
            'attack' => 'integer',
            'defense' => 'integer',
            'sp_attack' => 'integer',
            'sp_defense' => 'integer',
            'speed' => 'integer',
        ];
    }

    /**
     * Movimientos *posibles* (aprendibles) por esta especie.
     *
     * @return BelongsToMany<Move, $this>
     */
    public function moves(): BelongsToMany
    {
        return $this->belongsToMany(Move::class, 'pokemon_move');
    }

    /**
     * Instancias capturadas de esta especie.
     *
     * @return HasMany<MyPokemon, $this>
     */
    public function myPokemon(): HasMany
    {
        return $this->hasMany(MyPokemon::class);
    }
}
