<?php

declare(strict_types=1);

namespace App\Exceptions;

use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

/**
 * Se intentó jugar un turno en un combate ya terminado. Conflicto de estado → 409.
 */
final class BattleAlreadyFinishedException extends ConflictHttpException
{
    public function __construct()
    {
        parent::__construct('El combate ya ha terminado.');
    }
}
