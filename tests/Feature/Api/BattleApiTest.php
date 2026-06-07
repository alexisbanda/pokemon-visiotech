<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Domain\Combat\PokemonType;
use App\Domain\Combat\Random\FixedRandomFactor;
use App\Domain\Combat\Random\RandomFactor;
use App\Models\Move;
use App\Models\MyPokemon;
use App\Models\Pokemon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class BattleApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->instance(RandomFactor::class, new FixedRandomFactor(100));
    }

    /**
     * @param  array<string, int>  $statsOverrides
     * @param  list<array{name: string, power: int, type: PokemonType}>  $moves
     */
    private function makeMyPokemon(array $statsOverrides, PokemonType $type, array $moves = []): MyPokemon
    {
        $stats = [...['hp' => 100, 'attack' => 100, 'defense' => 50, 'sp_attack' => 50, 'sp_defense' => 50, 'speed' => 50], ...$statsOverrides];
        $pokemon = Pokemon::factory()->create([...$stats, 'type' => $type]);
        $moveModels = collect($moves)->map(fn (array $m) => Move::firstOrCreate(['name' => $m['name']], $m));
        $pokemon->moves()->attach($moveModels->pluck('id'));

        $myPokemon = MyPokemon::factory()->create(['pokemon_id' => $pokemon->id, 'level' => 50]);
        $myPokemon->moves()->sync($moveModels->pluck('id'));

        return $myPokemon;
    }

    private function surf(): array
    {
        return ['name' => 'Surf', 'power' => 80, 'type' => PokemonType::Water];
    }

    public function test_create_battle_returns_201_with_faster_combatant_on_turn(): void
    {
        $slow = $this->makeMyPokemon(['speed' => 40], PokemonType::Water, [$this->surf()]);
        $fast = $this->makeMyPokemon(['speed' => 99], PokemonType::Fire, [$this->surf()]);

        $this->postJson('/api/battles', [
            'first_my_pokemon_id' => $slow->id,
            'second_my_pokemon_id' => $fast->id,
        ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'in_progress')
            ->assertJsonPath('data.turn', 'second') // el rápido
            ->assertJsonPath('data.combatants.first.current_hp', $slow->maxHp()) // hp 100 → 160 a nivel 50
            ->assertJsonPath('data.combatants.first.max_hp', 160);
    }

    public function test_create_rejects_same_pokemon_on_both_sides(): void
    {
        $a = $this->makeMyPokemon(['speed' => 50], PokemonType::Water, [$this->surf()]);

        $this->postJson('/api/battles', [
            'first_my_pokemon_id' => $a->id,
            'second_my_pokemon_id' => $a->id,
        ])->assertUnprocessable()->assertJsonValidationErrors(['first_my_pokemon_id']);
    }

    public function test_create_rejects_combatant_without_moves(): void
    {
        $withMoves = $this->makeMyPokemon(['speed' => 50], PokemonType::Water, [$this->surf()]);
        $noMoves = $this->makeMyPokemon(['speed' => 50], PokemonType::Fire);

        $this->postJson('/api/battles', [
            'first_my_pokemon_id' => $withMoves->id,
            'second_my_pokemon_id' => $noMoves->id,
        ])->assertUnprocessable()->assertJsonValidationErrors(['second_my_pokemon_id']);
    }

    public function test_show_returns_current_state_and_log(): void
    {
        $attacker = $this->makeMyPokemon(['attack' => 100, 'speed' => 99], PokemonType::Water, [$this->surf()]);
        $defender = $this->makeMyPokemon(['defense' => 50, 'hp' => 200], PokemonType::Fire, [$this->surf()]);

        $battle = $this->postJson('/api/battles', [
            'first_my_pokemon_id' => $attacker->id,
            'second_my_pokemon_id' => $defender->id,
        ])->json('data.id');

        $this->postJson("/api/battles/{$battle}/turns", ['move_id' => $attacker->moves->first()->id]);

        $this->getJson("/api/battles/{$battle}")
            ->assertOk()
            ->assertJsonPath('data.id', $battle)
            ->assertJsonCount(1, 'data.turns')
            ->assertJsonPath('data.turns.0.label', 'super effective');
    }

    public function test_show_unknown_battle_returns_404(): void
    {
        $this->getJson('/api/battles/999')->assertNotFound();
    }

    public function test_turn_applies_damage_and_returns_200(): void
    {
        $attacker = $this->makeMyPokemon(['attack' => 100, 'speed' => 99], PokemonType::Water, [$this->surf()]);
        $defender = $this->makeMyPokemon(['defense' => 50, 'hp' => 200], PokemonType::Fire, [$this->surf()]);

        $id = $this->postJson('/api/battles', [
            'first_my_pokemon_id' => $attacker->id,
            'second_my_pokemon_id' => $defender->id,
        ])->json('data.id');

        $this->postJson("/api/battles/{$id}/turns", ['move_id' => $attacker->moves->first()->id])
            ->assertOk()
            ->assertJsonPath('data.combatants.second.current_hp', $defender->maxHp() - 140) // 260 - 140 = 120
            ->assertJsonPath('data.turn', 'second')
            ->assertJsonPath('data.turns.0.damage', 140);
    }

    public function test_battle_finishes_and_blocks_further_turns_with_409(): void
    {
        $attacker = $this->makeMyPokemon(['attack' => 100, 'speed' => 99], PokemonType::Water, [$this->surf()]);
        $defender = $this->makeMyPokemon(['defense' => 50, 'hp' => 40], PokemonType::Fire, [$this->surf()]);

        $id = $this->postJson('/api/battles', [
            'first_my_pokemon_id' => $attacker->id,
            'second_my_pokemon_id' => $defender->id,
        ])->json('data.id');

        $moveId = $attacker->moves->first()->id;

        $this->postJson("/api/battles/{$id}/turns", ['move_id' => $moveId])
            ->assertOk()
            ->assertJsonPath('data.status', 'finished')
            ->assertJsonPath('data.winner_my_pokemon_id', $attacker->id);

        // Un turno más sobre el combate terminado → 409.
        $this->postJson("/api/battles/{$id}/turns", ['move_id' => $moveId])
            ->assertStatus(409);
    }

    public function test_turn_with_foreign_move_returns_422(): void
    {
        $attacker = $this->makeMyPokemon(['speed' => 99], PokemonType::Water, [$this->surf()]);
        $defender = $this->makeMyPokemon([], PokemonType::Fire, [$this->surf()]);
        $foreign = Move::factory()->create();

        $id = $this->postJson('/api/battles', [
            'first_my_pokemon_id' => $attacker->id,
            'second_my_pokemon_id' => $defender->id,
        ])->json('data.id');

        $this->postJson("/api/battles/{$id}/turns", ['move_id' => $foreign->id])
            ->assertStatus(422);
    }

    public function test_turn_with_unknown_move_returns_422(): void
    {
        $attacker = $this->makeMyPokemon(['speed' => 99], PokemonType::Water, [$this->surf()]);
        $defender = $this->makeMyPokemon([], PokemonType::Fire, [$this->surf()]);

        $id = $this->postJson('/api/battles', [
            'first_my_pokemon_id' => $attacker->id,
            'second_my_pokemon_id' => $defender->id,
        ])->json('data.id');

        $this->postJson("/api/battles/{$id}/turns", ['move_id' => 99999])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['move_id']);
    }
}
