<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\MyPokemon;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBattleRequest extends FormRequest
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
            'first_my_pokemon_id' => ['required', 'integer', 'different:second_my_pokemon_id', Rule::exists('my_pokemon', 'id')],
            'second_my_pokemon_id' => ['required', 'integer', Rule::exists('my_pokemon', 'id')],
        ];
    }

    /**
     * Ambos combatientes deben tener al menos un movimiento para poder pelear.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            foreach (['first_my_pokemon_id', 'second_my_pokemon_id'] as $field) {
                $id = $this->input($field);
                $myPokemon = MyPokemon::find($id);

                if ($myPokemon !== null && $myPokemon->moves()->count() === 0) {
                    $validator->errors()->add($field, 'Este Pokémon no tiene movimientos y no puede combatir.');
                }
            }
        });
    }
}
