<?php

declare(strict_types=1);

namespace App\Domain\Combat;

/**
 * Movimiento (value object inmutable).
 *
 * El PDF lista solo nombre + poder, pero la efectividad exige conocer el tipo
 * del movimiento; por eso se añade `type`.
 */
final class Move
{
    public function __construct(
        public readonly string $name,
        public readonly int $power,
        public readonly PokemonType $type,
    ) {}
}
