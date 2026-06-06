<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TakeTurnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        // Que el movimiento pertenezca al atacante de turno lo valida el
        // BattleService (InvalidMoveException → 422), porque depende del estado.
        return [
            'move_id' => ['required', 'integer', Rule::exists('moves', 'id')],
        ];
    }
}
