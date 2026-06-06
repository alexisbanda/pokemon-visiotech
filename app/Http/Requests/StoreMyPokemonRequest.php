<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\MyPokemon;
use App\Models\Pokemon;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMyPokemonRequest extends FormRequest
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
            'pokemon_id' => ['required', 'integer', Rule::exists('pokemon', 'id')],
            'nickname' => ['required', 'string', 'max:255'],
            'level' => ['required', 'integer', 'min:1', 'max:100'],
            'moves' => ['sometimes', 'array', 'max:'.MyPokemon::MAX_MOVES],
            'moves.*' => ['integer', 'distinct', Rule::exists('moves', 'id')],
        ];
    }

    /**
     * Los movimientos elegidos deben estar entre los *posibles* de la especie.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $pokemonId = $this->input('pokemon_id');
            $moves = $this->input('moves', []);

            if (empty($moves) || ! is_array($moves)) {
                return;
            }

            $pokemon = Pokemon::find($pokemonId);

            if ($pokemon === null) {
                return; // ya lo cubre la regla exists de pokemon_id
            }

            $possible = $pokemon->moves()->pluck('moves.id')->all();
            $invalid = array_diff($moves, $possible);

            if ($invalid !== []) {
                $validator->errors()->add(
                    'moves',
                    'Alguno de los movimientos no está entre los aprendibles por esta especie.',
                );
            }
        });
    }
}
