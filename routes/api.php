<?php

declare(strict_types=1);

use App\Http\Controllers\MoveController;
use App\Http\Controllers\MyPokemonController;
use App\Http\Controllers\PokemonController;
use Illuminate\Support\Facades\Route;

// --- Consultas relacionales (Parte 2) ---
// Movimientos posibles (aprendibles) de un Pokémon base.
Route::get('pokemon/{pokemon}/moves', [PokemonController::class, 'moves']);
// Movimientos equipados de una instancia capturada.
Route::get('my-pokemon/{myPokemon}/moves', [MyPokemonController::class, 'moves']);
// Pokémon (especies) que comparten un mismo movimiento.
Route::get('moves/{move}/pokemon', [MoveController::class, 'pokemon']);

// --- CRUD de recursos ---
Route::apiResource('pokemon', PokemonController::class);
Route::apiResource('moves', MoveController::class);
Route::apiResource('my-pokemon', MyPokemonController::class)
    ->parameter('my-pokemon', 'myPokemon');
