<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreMoveRequest;
use App\Http\Requests\UpdateMoveRequest;
use App\Http\Resources\MoveResource;
use App\Http\Resources\PokemonResource;
use App\Models\Move;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class MoveController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return MoveResource::collection(Move::query()->orderBy('name')->paginate());
    }

    public function store(StoreMoveRequest $request): JsonResponse
    {
        $move = Move::create($request->validated());

        return MoveResource::make($move)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Move $move): MoveResource
    {
        return MoveResource::make($move);
    }

    public function update(UpdateMoveRequest $request, Move $move): MoveResource
    {
        $move->update($request->validated());

        return MoveResource::make($move);
    }

    public function destroy(Move $move): Response
    {
        $move->delete();

        return response()->noContent();
    }

    /**
     * Consulta: Pokémon (especies) que comparten un mismo movimiento.
     */
    public function pokemon(Move $move): AnonymousResourceCollection
    {
        return PokemonResource::collection($move->pokemon()->orderBy('name')->get());
    }
}
