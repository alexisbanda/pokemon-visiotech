<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Domain\Combat\PokemonType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePokemonRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255', Rule::unique('pokemon', 'name')],
            'type' => ['required', Rule::enum(PokemonType::class)],
            'hp' => ['required', 'integer', 'min:1', 'max:65535'],
            'attack' => ['required', 'integer', 'min:1', 'max:65535'],
            'defense' => ['required', 'integer', 'min:1', 'max:65535'],
            'sp_attack' => ['required', 'integer', 'min:1', 'max:65535'],
            'sp_defense' => ['required', 'integer', 'min:1', 'max:65535'],
            'speed' => ['required', 'integer', 'min:1', 'max:65535'],
        ];
    }
}
