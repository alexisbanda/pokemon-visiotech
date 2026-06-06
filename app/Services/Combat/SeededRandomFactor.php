<?php

declare(strict_types=1);

namespace App\Services\Combat;

use App\Domain\Combat\Random\RandomFactor;

/**
 * Factor aleatorio reproducible (85-100) a partir de una semilla, sin tocar el
 * estado global de mt_rand. Lo usa `battle:simulate --seed=N` para combates
 * idénticos en cada ejecución (demos y tests deterministas).
 */
final class SeededRandomFactor implements RandomFactor
{
    private int $state;

    public function __construct(int $seed)
    {
        $this->state = $seed & 0x7FFFFFFF;
    }

    public function value(): int
    {
        // Congruencial lineal determinista → entero en [85, 100].
        $this->state = ($this->state * 1103515245 + 12345) & 0x7FFFFFFF;

        return 85 + ($this->state % 16);
    }
}
