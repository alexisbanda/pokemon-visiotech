<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Combat\PokemonType;
use Illuminate\Http\JsonResponse;

class TypeController extends Controller
{
    /**
     * Lista los 18 tipos de Pokémon, en su orden de declaración en el enum.
     */
    public function index(): JsonResponse
    {
        $types = array_map(
            fn (PokemonType $type): array => ['value' => $type->value],
            PokemonType::cases(),
        );

        return response()->json(['data' => $types]);
    }
}
