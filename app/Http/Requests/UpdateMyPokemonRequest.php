<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\MyPokemon;
use App\Models\Pokemon;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMyPokemonRequest extends FormRequest
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
            'pokemon_id' => ['sometimes', 'required', 'integer', Rule::exists('pokemon', 'id')],
            'nickname' => ['sometimes', 'required', 'string', 'max:255'],
            'level' => ['sometimes', 'required', 'integer', 'min:1', 'max:100'],
            'moves' => ['sometimes', 'array', 'max:'.MyPokemon::MAX_MOVES],
            'moves.*' => ['integer', 'distinct', Rule::exists('moves', 'id')],
        ];
    }

    /**
     * Los movimientos elegidos deben estar entre los *posibles* de la especie
     * (la del payload si se cambia, o la actual del MyPokemon).
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (! $this->has('moves')) {
                return;
            }

            $moves = $this->input('moves', []);

            if (empty($moves) || ! is_array($moves)) {
                return;
            }

            /** @var MyPokemon $myPokemon */
            $myPokemon = $this->route('myPokemon');
            $pokemonId = $this->input('pokemon_id', $myPokemon->pokemon_id);
            $pokemon = Pokemon::find($pokemonId);

            if ($pokemon === null) {
                return;
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
