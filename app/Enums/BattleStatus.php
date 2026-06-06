<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Estado de la máquina de estados de un combate.
 */
enum BattleStatus: string
{
    case InProgress = 'in_progress';
    case Finished = 'finished';
}
