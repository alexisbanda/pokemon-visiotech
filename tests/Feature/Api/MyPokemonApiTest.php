<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Move;
use App\Models\MyPokemon;
use App\Models\Pokemon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

final class MyPokemonApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Crea una especie con N movimientos posibles.
     *
     * @return array{0: Pokemon, 1: Collection<int, Move>}
     */
    private function speciesWithMoves(int $moveCount = 5): array
    {
        $pokemon = Pokemon::factory()->create();
        $moves = Move::factory()->count($moveCount)->create();
        $pokemon->moves()->attach($moves);

        return [$pokemon, $moves];
    }

    public function test_store_creates_my_pokemon_with_moves_and_returns_201(): void
    {
        [$pokemon, $moves] = $this->speciesWithMoves();
        $chosen = $moves->take(4)->pluck('id')->all();

        $this->postJson('/api/my-pokemon', [
            'pokemon_id' => $pokemon->id,
            'nickname' => 'Sparky',
            'level' => 50,
            'moves' => $chosen,
        ])
            ->assertCreated()
            ->assertJsonPath('data.nickname', 'Sparky')
            ->assertJsonPath('data.pokemon.id', $pokemon->id)
            ->assertJsonCount(4, 'data.moves');

        $this->assertDatabaseHas('my_pokemon', ['nickname' => 'Sparky', 'level' => 50]);
        $this->assertDatabaseCount('my_pokemon_move', 4);
    }

    public function test_store_rejects_more_than_four_moves(): void
    {
        [$pokemon, $moves] = $this->speciesWithMoves(5);

        $this->postJson('/api/my-pokemon', [
            'pokemon_id' => $pokemon->id,
            'nickname' => 'Overloaded',
            'level' => 50,
            'moves' => $moves->pluck('id')->all(), // 5 movimientos
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['moves']);
    }

    public function test_store_rejects_move_not_learnable_by_species(): void
    {
        [$pokemon] = $this->speciesWithMoves(2);
        $foreign = Move::factory()->create(); // no está entre los posibles

        $this->postJson('/api/my-pokemon', [
            'pokemon_id' => $pokemon->id,
            'nickname' => 'Cheater',
            'level' => 50,
            'moves' => [$foreign->id],
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['moves']);
    }

    public function test_store_validates_required_fields(): void
    {
        $this->postJson('/api/my-pokemon', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['pokemon_id', 'nickname', 'level']);
    }

    public function test_update_replaces_moves(): void
    {
        [$pokemon, $moves] = $this->speciesWithMoves(5);
        $myPokemon = MyPokemon::factory()->create(['pokemon_id' => $pokemon->id]);
        $myPokemon->moves()->sync($moves->take(2)->pluck('id'));

        $newMoves = $moves->skip(2)->take(3)->pluck('id')->all();

        $this->patchJson("/api/my-pokemon/{$myPokemon->id}", ['moves' => $newMoves])
            ->assertOk()
            ->assertJsonCount(3, 'data.moves');

        $this->assertDatabaseCount('my_pokemon_move', 3);
    }

    public function test_moves_endpoint_returns_equipped_moves(): void
    {
        [$pokemon, $moves] = $this->speciesWithMoves(4);
        $myPokemon = MyPokemon::factory()->create(['pokemon_id' => $pokemon->id]);
        $myPokemon->moves()->sync($moves->take(2)->pluck('id'));

        $this->getJson("/api/my-pokemon/{$myPokemon->id}/moves")
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_destroy_deletes_instance(): void
    {
        $myPokemon = MyPokemon::factory()->create();

        $this->deleteJson("/api/my-pokemon/{$myPokemon->id}")->assertNoContent();
        $this->assertDatabaseMissing('my_pokemon', ['id' => $myPokemon->id]);
    }
}
