<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StorePokemonRequest;
use App\Http\Requests\UpdatePokemonRequest;
use App\Http\Resources\MoveResource;
use App\Http\Resources\PokemonResource;
use App\Models\Move;
use App\Models\Pokemon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class PokemonController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return PokemonResource::collection(Pokemon::query()->orderBy('name')->paginate());
    }

    public function store(StorePokemonRequest $request): JsonResponse
    {
        $pokemon = Pokemon::create($request->validated());

        return PokemonResource::make($pokemon)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Pokemon $pokemon): PokemonResource
    {
        return PokemonResource::make($pokemon->load('moves'));
    }

    public function update(UpdatePokemonRequest $request, Pokemon $pokemon): PokemonResource
    {
        $pokemon->update($request->validated());

        return PokemonResource::make($pokemon);
    }

    public function destroy(Pokemon $pokemon): Response
    {
        $pokemon->delete();

        return response()->noContent();
    }

    /**
     * Consulta: movimientos *posibles* (aprendibles) de un Pokémon.
     */
    public function moves(Pokemon $pokemon): AnonymousResourceCollection
    {
        return MoveResource::collection($pokemon->moves()->orderBy('name')->get());
    }

    /**
     * Consulta: movimientos cuyo tipo coincide con el del Pokémon
     * (la relación movimientos → tipo → Pokémon del enunciado).
     */
    public function movesByType(Pokemon $pokemon): AnonymousResourceCollection
    {
        return MoveResource::collection(
            Move::query()->where('type', $pokemon->type)->orderBy('name')->get(),
        );
    }
}
