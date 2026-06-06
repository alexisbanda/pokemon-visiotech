<?php

declare(strict_types=1);

namespace App\Domain\Combat\Random;

use InvalidArgumentException;

/**
 * Implementación determinista para tests: devuelve siempre el mismo valor.
 */
final class FixedRandomFactor implements RandomFactor
{
    public function __construct(private readonly int $value)
    {
        if ($value < StandardRandomFactor::MIN || $value > StandardRandomFactor::MAX) {
            throw new InvalidArgumentException(
                "El factor aleatorio debe estar entre 85 y 100, recibido: {$value}."
            );
        }
    }

    public function value(): int
    {
        return $this->value;
    }
}
