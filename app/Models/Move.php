<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Combat\PokemonType;
use Database\Factories\MoveFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Movimiento de la Pokédex (name, power, type).
 *
 * @property-read Collection<int, Pokemon> $pokemon
 */
class Move extends Model
{
    /** @use HasFactory<MoveFactory> */
    use HasFactory;

    protected $fillable = ['name', 'power', 'type'];

    protected function casts(): array
    {
        return [
            'type' => PokemonType::class,
            'power' => 'integer',
        ];
    }

    /**
     * Pokémon (base) que pueden aprender este movimiento.
     *
     * @return BelongsToMany<Pokemon, $this>
     */
    public function pokemon(): BelongsToMany
    {
        return $this->belongsToMany(Pokemon::class, 'pokemon_move');
    }

    /**
     * Instancias (MyPokemon) que tienen este movimiento equipado.
     *
     * @return BelongsToMany<MyPokemon, $this>
     */
    public function myPokemon(): BelongsToMany
    {
        return $this->belongsToMany(MyPokemon::class, 'my_pokemon_move');
    }
}
