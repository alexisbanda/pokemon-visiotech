<?php

declare(strict_types=1);

namespace App\Domain\Combat;

use App\Domain\Combat\Random\RandomFactor;

/**
 * Motor de daño (Parte 1). PHP puro, sin dependencias de Laravel.
 *
 * Fórmula fiel al PDF (NO incluye el "+2" final de la fórmula real del juego):
 *
 *   base   = floor( (2 * level / 5 + 2) * attack * movePower / defense / 50 )
 *   damage = floor( base * effectiveness * random / 100 )
 *
 * - attack  = Ataque base del atacante.
 * - defense = Defensa base del defensor.
 *   (Punto de extensión: si el Move tuviera categoría físico/especial se usarían
 *    SpAttack/SpDefense. No se implementa por fidelidad al enunciado.)
 * - effectiveness = TypeChart(move.type vs defender.type).
 * - random = factor inyectado (85–100).
 */
final class DamageCalculator
{
    public function __construct(private readonly RandomFactor $random) {}

    public function calculate(Combatant $attacker, Move $move, Combatant $defender): DamageResult
    {
        $effectiveness = TypeChart::multiplier($move->type, $defender->type);
        $random = $this->random->value();

        $base = (int) floor(
            (2 * $attacker->level / 5 + 2)
            * $attacker->stats->attack
            * $move->power
            / $defender->stats->defense
            / 50
        );

        $damage = (int) floor($base * $effectiveness * $random / 100);

        return new DamageResult($damage, $effectiveness, $this->labelFor($effectiveness));
    }

    private function labelFor(float $effectiveness): string
    {
        return match (true) {
            $effectiveness === TypeChart::NO_EFFECT => DamageResult::LABEL_NO_EFFECT,
            $effectiveness > TypeChart::NEUTRAL => DamageResult::LABEL_SUPER_EFFECTIVE,
            $effectiveness < TypeChart::NEUTRAL => DamageResult::LABEL_NOT_VERY_EFFECTIVE,
            default => DamageResult::LABEL_NORMAL,
        };
    }
}
