<?php

declare(strict_types=1);

namespace Tests\Feature\Combat;

use App\Domain\Combat\PokemonType;
use App\Enums\BattleStatus;
use App\Models\Move;
use App\Models\MyPokemon;
use App\Models\Pokemon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SimulateBattleCommandTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, int>  $statsOverrides
     * @param  list<array{name: string, power: int, type: PokemonType}>  $moves
     */
    private function makeMyPokemon(array $statsOverrides, PokemonType $type, array $moves): MyPokemon
    {
        $stats = [...['hp' => 100, 'attack' => 100, 'defense' => 50, 'sp_attack' => 50, 'sp_defense' => 50, 'speed' => 50], ...$statsOverrides];
        $pokemon = Pokemon::factory()->create([...$stats, 'type' => $type]);
        $moveModels = collect($moves)->map(fn (array $m) => Move::firstOrCreate(['name' => $m['name']], $m));
        $pokemon->moves()->attach($moveModels->pluck('id'));

        $myPokemon = MyPokemon::factory()->create(['pokemon_id' => $pokemon->id, 'level' => 50]);
        $myPokemon->moves()->sync($moveModels->pluck('id'));

        return $myPokemon;
    }

    public function test_simulation_runs_to_completion_and_declares_the_winner(): void
    {
        // Atacante rápido y fuerte con golpe supereficaz; rival con 1 PS → KO en
        // el turno 1 pase lo que pase con el azar. Ganador determinista.
        $winner = $this->makeMyPokemon(
            ['attack' => 120, 'speed' => 99],
            PokemonType::Water,
            [['name' => 'Surf', 'power' => 90, 'type' => PokemonType::Water]],
        );
        $loser = $this->makeMyPokemon(
            ['defense' => 40, 'hp' => 1, 'speed' => 10],
            PokemonType::Fire,
            [['name' => 'Ember', 'power' => 40, 'type' => PokemonType::Fire]],
        );

        $this->artisan('battle:simulate', [
            'first' => $winner->id,
            'second' => $loser->id,
            '--no-delay' => true,
            '--ascii' => true,
        ])
            ->expectsOutputToContain("¡Gana {$winner->nickname}!")
            ->assertExitCode(0);

        $this->assertDatabaseHas('battles', [
            'winner_my_pokemon_id' => $winner->id,
            'status' => BattleStatus::Finished->value,
        ]);
    }

    public function test_selects_combatants_from_a_menu_when_ids_are_omitted(): void
    {
        // Tres combatientes tipo Normal con un golpe Normal: siempre se hacen
        // daño (×1), así que la batalla termina sin riesgo de bucle.
        $normalMove = ['name' => 'Pound', 'power' => 100, 'type' => PokemonType::Normal];
        $a = $this->makeMyPokemon(['attack' => 120, 'defense' => 40, 'hp' => 60], PokemonType::Normal, [$normalMove]);
        $b = $this->makeMyPokemon(['attack' => 120, 'defense' => 40, 'hp' => 60], PokemonType::Normal, [$normalMove]);
        $this->makeMyPokemon(['attack' => 120, 'defense' => 40, 'hp' => 60], PokemonType::Normal, [$normalMove]);

        $this->artisan('battle:simulate', ['--no-delay' => true, '--ascii' => true])
            ->expectsQuestion('Elige el primer combatiente', $a->id)
            ->expectsQuestion('Elige el rival', $b->id)
            ->assertExitCode(0);

        $this->assertDatabaseCount('battles', 1);
    }

    public function test_fails_when_a_combatant_does_not_exist(): void
    {
        $existing = $this->makeMyPokemon([], PokemonType::Water, [['name' => 'Surf', 'power' => 90, 'type' => PokemonType::Water]]);

        $this->artisan('battle:simulate', ['first' => $existing->id, 'second' => 9999])
            ->assertExitCode(1);
    }

    public function test_fails_when_a_combatant_has_no_moves(): void
    {
        $withMoves = $this->makeMyPokemon([], PokemonType::Water, [['name' => 'Surf', 'power' => 90, 'type' => PokemonType::Water]]);
        $pokemon = Pokemon::factory()->create(['type' => PokemonType::Fire]);
        $noMoves = MyPokemon::factory()->create(['pokemon_id' => $pokemon->id]);

        $this->artisan('battle:simulate', ['first' => $withMoves->id, 'second' => $noMoves->id])
            ->assertExitCode(1);
    }
}
