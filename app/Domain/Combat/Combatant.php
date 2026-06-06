<?php

declare(strict_types=1);

namespace App\Domain\Combat;

/**
 * Pokémon participante en un combate (value object inmutable).
 *
 * `currentHp` representa los PS actuales; al aplicar daño se obtiene una nueva
 * instancia con `withCurrentHp()`, manteniendo la inmutabilidad del dominio.
 */
final class Combatant
{
    /**
     * @param  list<Move>  $moves  movimientos elegidos (máx. 4)
     */
    public function __construct(
        public readonly string $name,
        public readonly int $level,
        public readonly PokemonType $type,
        public readonly Stats $stats,
        public readonly int $currentHp,
        public readonly array $moves = [],
    ) {}

    public function withCurrentHp(int $currentHp): self
    {
        return new self(
            $this->name,
            $this->level,
            $this->type,
            $this->stats,
            max(0, $currentHp),
            $this->moves,
        );
    }

    public function isFainted(): bool
    {
        return $this->currentHp <= 0;
    }
}
