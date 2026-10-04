<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Move;
use App\Models\Pokemon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PokemonApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_lists_pokemon(): void
    {
        Pokemon::factory()->count(3)->create();

        $this->getJson('/api/pokemon')
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure(['data' => [['id', 'name', 'type', 'stats' => ['hp', 'attack', 'speed']]]]);
    }

    public function test_index_filters_by_type_ordered_by_name_and_keeps_query_string(): void
    {
        foreach (['Vulpix', 'Arcanine', 'Charmander'] as $name) {
            Pokemon::factory()->create(['name' => $name, 'type' => 'fire']);
        }
        Pokemon::factory()->create(['name' => 'Squirtle', 'type' => 'water']);
        Pokemon::factory()->create(['name' => 'Bulbasaur', 'type' => 'grass']);

        $response = $this->getJson('/api/pokemon?type=fire')
            ->assertOk()
            ->assertJsonStructure(['data', 'links', 'meta'])
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('meta.total', 3);

        $this->assertSame(['Arcanine', 'Charmander', 'Vulpix'], array_column($response->json('data'), 'name'));
        $this->assertSame(['fire'], array_unique(array_column($response->json('data'), 'type')));
        $this->assertStringContainsString('type=fire', $response->json('links.first'));
    }

    public function test_index_without_type_returns_all_types(): void
    {
        Pokemon::factory()->create(['type' => 'fire']);
        Pokemon::factory()->create(['type' => 'water']);
        Pokemon::factory()->create(['type' => 'grass']);

        $this->getJson('/api/pokemon')
            ->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_index_rejects_unknown_type(): void
    {
        $this->getJson('/api/pokemon?type=banana')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['type']);
    }

    public function test_index_type_filter_is_case_sensitive(): void
    {
        $this->getJson('/api/pokemon?type=Fire')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['type']);
    }

    public function test_store_creates_pokemon_and_returns_201(): void
    {
        $payload = [
            'name' => 'Charizard',
            'type' => 'fire',
            'hp' => 78, 'attack' => 84, 'defense' => 78,
            'sp_attack' => 109, 'sp_defense' => 85, 'speed' => 100,
        ];

        $this->postJson('/api/pokemon', $payload)
            ->assertCreated()
            ->assertJsonPath('data.name', 'Charizard')
            ->assertJsonPath('data.type', 'fire');

        $this->assertDatabaseHas('pokemon', ['name' => 'Charizard', 'type' => 'fire']);
    }

    public function test_store_validates_required_fields_and_enum_type(): void
    {
        $this->postJson('/api/pokemon', ['name' => 'X', 'type' => 'plasma'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['type', 'hp', 'attack', 'defense', 'sp_attack', 'sp_defense', 'speed']);
    }

    public function test_store_rejects_duplicate_name(): void
    {
        Pokemon::factory()->create(['name' => 'Pikachu']);

        $this->postJson('/api/pokemon', [
            'name' => 'Pikachu', 'type' => 'electric',
            'hp' => 35, 'attack' => 55, 'defense' => 40,
            'sp_attack' => 50, 'sp_defense' => 50, 'speed' => 90,
        ])->assertUnprocessable()->assertJsonValidationErrors(['name']);
    }

    public function test_show_returns_pokemon_with_moves(): void
    {
        $pokemon = Pokemon::factory()->create();
        $move = Move::factory()->create();
        $pokemon->moves()->attach($move);

        $this->getJson("/api/pokemon/{$pokemon->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $pokemon->id)
            ->assertJsonPath('data.moves.0.id', $move->id);
    }

    public function test_show_unknown_pokemon_returns_404(): void
    {
        $this->getJson('/api/pokemon/999')->assertNotFound();
    }

    public function test_update_modifies_pokemon(): void
    {
        $pokemon = Pokemon::factory()->create(['attack' => 50]);

        $this->patchJson("/api/pokemon/{$pokemon->id}", ['attack' => 120])
            ->assertOk()
            ->assertJsonPath('data.stats.attack', 120);

        $this->assertDatabaseHas('pokemon', ['id' => $pokemon->id, 'attack' => 120]);
    }

    public function test_destroy_deletes_pokemon_and_returns_204(): void
    {
        $pokemon = Pokemon::factory()->create();

        $this->deleteJson("/api/pokemon/{$pokemon->id}")->assertNoContent();

        $this->assertDatabaseMissing('pokemon', ['id' => $pokemon->id]);
    }

    public function test_moves_endpoint_returns_possible_moves(): void
    {
        $pokemon = Pokemon::factory()->create();
        $known = Move::factory()->count(2)->create();
        $other = Move::factory()->create();
        $pokemon->moves()->attach($known);

        $this->getJson("/api/pokemon/{$pokemon->id}/moves")
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonMissing(['id' => $other->id]);
    }

    public function test_moves_by_type_endpoint_returns_moves_of_the_pokemons_type(): void
    {
        $pokemon = Pokemon::factory()->create(['type' => 'fire']);
        Move::factory()->count(2)->create(['type' => 'fire']);
        $water = Move::factory()->create(['type' => 'water']);

        $this->getJson("/api/pokemon/{$pokemon->id}/moves-by-type")
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonMissing(['id' => $water->id]);
    }
}
