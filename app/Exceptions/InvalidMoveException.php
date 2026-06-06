<?php

declare(strict_types=1);

namespace App\Exceptions;

use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * El movimiento solicitado no pertenece al atacante de turno → 422.
 */
final class InvalidMoveException extends UnprocessableEntityHttpException
{
    public function __construct()
    {
        parent::__construct('El movimiento no pertenece al Pokémon que ataca en este turno.');
    }
}
