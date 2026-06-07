<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\MyPokemonFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Instancia capturada: un Pokémon base con nivel, mote y hasta 4 movimientos.
 *
 * @property-read Pokemon $pokemon
 * @property-read Collection<int, Move> $moves
 */
class MyPokemon extends Model
{
    /** @use HasFactory<MyPokemonFactory> */
    use HasFactory;

    /** Número máximo de movimientos equipables (regla del enunciado). */
    public const int MAX_MOVES = 4;

    protected $table = 'my_pokemon';

    protected $fillable = ['pokemon_id', 'nickname', 'level'];

    protected function casts(): array
    {
        return [
            'level' => 'integer',
        ];
    }

    /**
     * PS máximos al nivel actual (fórmula estándar de PS de la saga). Se usan
     * para inicializar el combate; sin escalar al nivel, el daño del enunciado
     * dejaría las peleas en uno o dos golpes.
     */
    public function maxHp(): int
    {
        return (int) floor(2 * $this->pokemon->hp * $this->level / 100) + $this->level + 10;
    }

    /**
     * Especie base de la que hereda stats y tipo.
     *
     * @return BelongsTo<Pokemon, $this>
     */
    public function pokemon(): BelongsTo
    {
        return $this->belongsTo(Pokemon::class);
    }

    /**
     * Movimientos equipados (máx. 4).
     *
     * @return BelongsToMany<Move, $this>
     */
    public function moves(): BelongsToMany
    {
        return $this->belongsToMany(Move::class, 'my_pokemon_move');
    }
}
