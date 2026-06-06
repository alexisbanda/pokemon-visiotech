<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Move;
use App\Models\Pokemon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class MoveApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_creates_move_and_returns_201(): void
    {
        $this->postJson('/api/moves', ['name' => 'Surf', 'power' => 90, 'type' => 'water'])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Surf')
            ->assertJsonPath('data.power', 90);

        $this->assertDatabaseHas('moves', ['name' => 'Surf', 'type' => 'water']);
    }

    public function test_store_validates_enum_type(): void
    {
        $this->postJson('/api/moves', ['name' => 'Bad', 'power' => 50, 'type' => 'plasma'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['type']);
    }

    public function test_update_and_destroy(): void
    {
        $move = Move::factory()->create(['power' => 40]);

        $this->patchJson("/api/moves/{$move->id}", ['power' => 95])
            ->assertOk()
            ->assertJsonPath('data.power', 95);

        $this->deleteJson("/api/moves/{$move->id}")->assertNoContent();
        $this->assertDatabaseMissing('moves', ['id' => $move->id]);
    }

    public function test_pokemon_endpoint_lists_species_that_share_a_move(): void
    {
        $move = Move::factory()->create();
        [$a, $b] = Pokemon::factory()->count(2)->create();
        $loner = Pokemon::factory()->create();
        $a->moves()->attach($move);
        $b->moves()->attach($move);

        $this->getJson("/api/moves/{$move->id}/pokemon")
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonMissing(['id' => $loner->id]);
    }
}
