<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreMyPokemonRequest;
use App\Http\Requests\UpdateMyPokemonRequest;
use App\Http\Resources\MoveResource;
use App\Http\Resources\MyPokemonResource;
use App\Models\MyPokemon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class MyPokemonController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return MyPokemonResource::collection(
            MyPokemon::query()->with(['pokemon', 'moves'])->orderBy('nickname')->paginate(),
        );
    }

    public function store(StoreMyPokemonRequest $request): JsonResponse
    {
        $myPokemon = MyPokemon::create($request->safe()->except('moves'));
        $myPokemon->moves()->sync($request->input('moves', []));

        return MyPokemonResource::make($myPokemon->load(['pokemon', 'moves']))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(MyPokemon $myPokemon): MyPokemonResource
    {
        return MyPokemonResource::make($myPokemon->load(['pokemon', 'moves']));
    }

    public function update(UpdateMyPokemonRequest $request, MyPokemon $myPokemon): MyPokemonResource
    {
        $myPokemon->update($request->safe()->except('moves'));

        if ($request->has('moves')) {
            $myPokemon->moves()->sync($request->input('moves', []));
        }

        return MyPokemonResource::make($myPokemon->load(['pokemon', 'moves']));
    }

    public function destroy(MyPokemon $myPokemon): Response
    {
        $myPokemon->delete();

        return response()->noContent();
    }

    /**
     * Consulta: movimientos equipados de un MyPokemon.
     */
    public function moves(MyPokemon $myPokemon): AnonymousResourceCollection
    {
        return MoveResource::collection($myPokemon->moves()->orderBy('name')->get());
    }
}
