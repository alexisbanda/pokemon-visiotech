<?php

declare(strict_types=1);

namespace App\Domain\Combat;

/**
 * Resultado del cálculo de daño (value object inmutable).
 */
final class DamageResult
{
    public const string LABEL_SUPER_EFFECTIVE = 'super effective';

    public const string LABEL_NOT_VERY_EFFECTIVE = 'not very effective';

    public const string LABEL_NO_EFFECT = 'no effect';

    public const string LABEL_NORMAL = 'normal';

    public function __construct(
        public readonly int $damage,
        public readonly float $effectivenessMultiplier,
        public readonly string $label,
    ) {}
}
