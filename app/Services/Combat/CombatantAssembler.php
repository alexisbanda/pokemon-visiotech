<?php

declare(strict_types=1);

namespace App\Services\Combat;

use App\Domain\Combat\Combatant;
use App\Domain\Combat\Move as DomainMove;
use App\Domain\Combat\Stats;
use App\Models\Move as EloquentMove;
use App\Models\MyPokemon;

/**
 * Puente entre la persistencia (Eloquent) y el dominio puro de P1.
 *
 * Traduce un MyPokemon + sus PS actuales en un `Combatant` del dominio, y un
 * Move de Eloquent en el `Move` del dominio. Vive en la capa de servicios
 * (NO en app/Domain) para que el núcleo de combate siga sin tocar Laravel.
 */
final class CombatantAssembler
{
    public function toCombatant(MyPokemon $myPokemon, int $currentHp): Combatant
    {
        $base = $myPokemon->pokemon;

        return new Combatant(
            name: $myPokemon->nickname,
            level: $myPokemon->level,
            type: $base->type,
            stats: new Stats(
                hp: $base->hp,
                attack: $base->attack,
                defense: $base->defense,
                spAttack: $base->sp_attack,
                spDefense: $base->sp_defense,
                speed: $base->speed,
            ),
            currentHp: $currentHp,
            moves: $myPokemon->moves->map(fn (EloquentMove $move) => $this->toMove($move))->all(),
        );
    }

    public function toMove(EloquentMove $move): DomainMove
    {
        return new DomainMove($move->name, $move->power, $move->type);
    }
}
