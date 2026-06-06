<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Cuál de los dos combatientes (el primero o el segundo del combate).
 */
enum BattleSide: string
{
    case First = 'first';
    case Second = 'second';

    public function opponent(): self
    {
        return $this === self::First ? self::Second : self::First;
    }
}
