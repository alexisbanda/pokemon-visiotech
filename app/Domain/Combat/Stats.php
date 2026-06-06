<?php

declare(strict_types=1);

namespace App\Domain\Combat;

/**
 * Estadísticas base de un Pokémon (value object inmutable).
 */
final class Stats
{
    public function __construct(
        public readonly int $hp,
        public readonly int $attack,
        public readonly int $defense,
        public readonly int $spAttack,
        public readonly int $spDefense,
        public readonly int $speed,
    ) {}
}
