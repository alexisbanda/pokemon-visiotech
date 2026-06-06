<?php

declare(strict_types=1);

namespace App\Domain\Combat\Random;

/**
 * Implementación de producción: entero aleatorio entre 85 y 100
 * (provoca una variación de hasta ±15% en el daño).
 */
final class StandardRandomFactor implements RandomFactor
{
    public const int MIN = 85;

    public const int MAX = 100;

    public function value(): int
    {
        return random_int(self::MIN, self::MAX);
    }
}
