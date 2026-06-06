<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Domain\Combat\PokemonType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePokemonRequest extends FormRequest
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
        $pokemonId = $this->route('pokemon')->id;

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('pokemon', 'name')->ignore($pokemonId)],
            'type' => ['sometimes', 'required', Rule::enum(PokemonType::class)],
            'hp' => ['sometimes', 'required', 'integer', 'min:1', 'max:65535'],
            'attack' => ['sometimes', 'required', 'integer', 'min:1', 'max:65535'],
            'defense' => ['sometimes', 'required', 'integer', 'min:1', 'max:65535'],
            'sp_attack' => ['sometimes', 'required', 'integer', 'min:1', 'max:65535'],
            'sp_defense' => ['sometimes', 'required', 'integer', 'min:1', 'max:65535'],
            'speed' => ['sometimes', 'required', 'integer', 'min:1', 'max:65535'],
        ];
    }
}
