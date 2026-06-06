<?php

declare(strict_types=1);

namespace App\Domain\Combat\Random;

/**
 * Factor aleatorio de la fórmula de daño (85–100).
 * Se inyecta tras una interfaz para que el cálculo sea determinista en tests.
 */
interface RandomFactor
{
    public function value(): int;
}
