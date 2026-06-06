<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Domain\Combat\PokemonType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMoveRequest extends FormRequest
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
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('moves', 'name')],
            'power' => ['required', 'integer', 'min:0', 'max:65535'],
            'type' => ['required', Rule::enum(PokemonType::class)],
        ];
    }
}
