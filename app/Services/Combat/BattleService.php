<?php

declare(strict_types=1);

namespace App\Services\Combat;

use App\Domain\Combat\DamageCalculator;
use App\Enums\BattleSide;
use App\Enums\BattleStatus;
use App\Exceptions\BattleAlreadyFinishedException;
use App\Exceptions\InvalidMoveException;
use App\Models\Battle;
use App\Models\BattleTurn;
use App\Models\Move;
use App\Models\MyPokemon;
use Illuminate\Support\Facades\DB;

/**
 * Orquesta el combate por turnos (máquina de estados). Toda la lógica vive aquí;
 * los controllers solo validan y delegan. Reutiliza el DamageCalculator de P1
 * a través del CombatantAssembler.
 */
final class BattleService
{
    public function __construct(
        private readonly DamageCalculator $calculator,
        private readonly CombatantAssembler $assembler,
    ) {}

    /**
     * Crea un combate. El primer turno es del de mayor Velocidad; en caso de
     * empate, ataca primero el combatiente "first" (determinista).
     */
    public function create(MyPokemon $first, MyPokemon $second): Battle
    {
        $turn = $second->pokemon->speed > $first->pokemon->speed
            ? BattleSide::Second
            : BattleSide::First;

        return Battle::create([
            'first_my_pokemon_id' => $first->id,
            'second_my_pokemon_id' => $second->id,
            'first_current_hp' => $first->pokemon->hp,
            'second_current_hp' => $second->pokemon->hp,
            'turn' => $turn,
            'status' => BattleStatus::InProgress,
            'turn_number' => 0,
        ]);
    }

    /**
     * Ejecuta una fase: el atacante de turno usa un movimiento.
     *
     * @throws BattleAlreadyFinishedException si el combate ya terminó (409)
     * @throws InvalidMoveException si el movimiento no es del atacante (422)
     */
    public function takeTurn(Battle $battle, Move $move): BattleTurn
    {
        if ($battle->isFinished()) {
            throw new BattleAlreadyFinishedException;
        }

        $attackerSide = $battle->turn;
        $defenderSide = $attackerSide->opponent();
        $attacker = $battle->combatantOn($attackerSide);

        if (! $attacker->moves->contains('id', $move->id)) {
            throw new InvalidMoveException;
        }

        $result = $this->calculator->calculate(
            $this->assembler->toCombatant($attacker, $battle->currentHp($attackerSide)),
            $this->assembler->toMove($move),
            $this->assembler->toCombatant($battle->combatantOn($defenderSide), $battle->currentHp($defenderSide)),
        );

        $defenderHp = max(0, $battle->currentHp($defenderSide) - $result->damage);
        $battle->setCurrentHp($defenderSide, $defenderHp);
        $battle->turn_number++;

        $turn = new BattleTurn([
            'number' => $battle->turn_number,
            'attacker' => $attackerSide,
            'move_id' => $move->id,
            'damage' => $result->damage,
            'effectiveness' => $result->effectivenessMultiplier,
            'label' => $result->label,
            'defender_hp_after' => $defenderHp,
        ]);

        if ($defenderHp <= 0) {
            $battle->status = BattleStatus::Finished;
            $battle->winner_my_pokemon_id = $attacker->id;
        } else {
            $battle->turn = $defenderSide;
        }

        DB::transaction(function () use ($battle, $turn): void {
            $battle->save();
            $battle->turns()->save($turn);
        });

        return $turn;
    }
}
