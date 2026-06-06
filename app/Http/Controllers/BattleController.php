<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreBattleRequest;
use App\Http\Requests\TakeTurnRequest;
use App\Http\Resources\BattleResource;
use App\Models\Battle;
use App\Models\Move;
use App\Models\MyPokemon;
use App\Services\Combat\BattleService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class BattleController extends Controller
{
    public function __construct(private readonly BattleService $battles) {}

    public function store(StoreBattleRequest $request): JsonResponse
    {
        $first = MyPokemon::with(['pokemon', 'moves'])->findOrFail($request->integer('first_my_pokemon_id'));
        $second = MyPokemon::with(['pokemon', 'moves'])->findOrFail($request->integer('second_my_pokemon_id'));

        $battle = $this->battles->create($first, $second);

        return BattleResource::make($this->withState($battle))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Battle $battle): BattleResource
    {
        return BattleResource::make($this->withState($battle, withLog: true));
    }

    public function turns(TakeTurnRequest $request, Battle $battle): BattleResource
    {
        $move = Move::findOrFail($request->integer('move_id'));
        $battle->load([
            'firstMyPokemon.pokemon', 'firstMyPokemon.moves',
            'secondMyPokemon.pokemon', 'secondMyPokemon.moves',
        ]);

        // El servicio aplica el turno y lanza 409/422 ante transiciones inválidas.
        $this->battles->takeTurn($battle, $move);

        return BattleResource::make($this->withState($battle->refresh(), withLog: true));
    }

    private function withState(Battle $battle, bool $withLog = false): Battle
    {
        $battle->load([
            'firstMyPokemon.pokemon',
            'firstMyPokemon.moves',
            'secondMyPokemon.pokemon',
            'secondMyPokemon.moves',
        ]);

        if ($withLog) {
            $battle->load('turns.move');
        }

        return $battle;
    }
}
