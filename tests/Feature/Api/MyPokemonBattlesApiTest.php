<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Enums\BattleStatus;
use App\Models\Battle;
use App\Models\MyPokemon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class MyPokemonBattlesApiTest extends TestCase
{
    use RefreshDatabase;

    private function createBattle(
        MyPokemon $first,
        MyPokemon $second,
        BattleStatus $status,
        ?MyPokemon $winner,
        string $createdAt,
    ): Battle {
        return Battle::factory()->create([
            'first_my_pokemon_id' => $first->id,
            'second_my_pokemon_id' => $second->id,
            'status' => $status,
            'winner_my_pokemon_id' => $winner?->id,
            'created_at' => $createdAt,
        ]);
    }

    public function test_lists_battles_from_both_sides_with_result_and_summary(): void
    {
        $hero = MyPokemon::factory()->create(['nickname' => 'Sparky']);
        $rivalA = MyPokemon::factory()->create(['nickname' => 'Rocky']);
        $rivalB = MyPokemon::factory()->create(['nickname' => 'Bubbles']);
        $rivalC = MyPokemon::factory()->create(['nickname' => 'Flare']);

        // Ganado jugando como "first".
        $won1 = $this->createBattle($hero, $rivalA, BattleStatus::Finished, $hero, '2024-01-01 10:00:00');
        // Ganado jugando como "second".
        $won2 = $this->createBattle($rivalB, $hero, BattleStatus::Finished, $hero, '2024-01-02 10:00:00');
        // Perdido jugando como "first".
        $lost = $this->createBattle($hero, $rivalC, BattleStatus::Finished, $rivalC, '2024-01-03 10:00:00');
        // En curso.
        $inProgress = $this->createBattle($hero, $rivalA, BattleStatus::InProgress, null, '2024-01-04 10:00:00');

        $response = $this->getJson("/api/my-pokemon/{$hero->id}/battles")
            ->assertOk()
            ->assertJsonCount(4, 'data')
            ->assertJsonPath('summary.wins', 2)
            ->assertJsonPath('summary.losses', 1);

        // Orden del más reciente al más antiguo.
        $response->assertJsonPath('data.0.id', $inProgress->id);
        $response->assertJsonPath('data.0.result', 'in_progress');
        $response->assertJsonPath('data.1.id', $lost->id);
        $response->assertJsonPath('data.1.result', 'lost');
        $response->assertJsonPath('data.1.rival.id', $rivalC->id);
        $response->assertJsonPath('data.1.rival.nickname', 'Flare');
        $response->assertJsonPath('data.2.id', $won2->id);
        $response->assertJsonPath('data.2.result', 'won');
        $response->assertJsonPath('data.2.rival.id', $rivalB->id);
        $response->assertJsonPath('data.3.id', $won1->id);
        $response->assertJsonPath('data.3.result', 'won');
        $response->assertJsonPath('data.3.rival.id', $rivalA->id);
        $response->assertJsonPath('data.3.rival.pokemon.id', $rivalA->pokemon_id);
    }

    public function test_returns_empty_list_with_zeroed_summary_when_no_battles(): void
    {
        $myPokemon = MyPokemon::factory()->create();

        $this->getJson("/api/my-pokemon/{$myPokemon->id}/battles")
            ->assertOk()
            ->assertJsonCount(0, 'data')
            ->assertJsonPath('summary.wins', 0)
            ->assertJsonPath('summary.losses', 0);
    }

    public function test_returns_404_for_nonexistent_my_pokemon(): void
    {
        $this->getJson('/api/my-pokemon/999/battles')->assertNotFound();
    }

    public function test_does_not_include_battles_of_other_pokemon(): void
    {
        $myPokemon = MyPokemon::factory()->create();
        $other1 = MyPokemon::factory()->create();
        $other2 = MyPokemon::factory()->create();
        $this->createBattle($other1, $other2, BattleStatus::Finished, $other1, '2024-01-01 10:00:00');

        $this->getJson("/api/my-pokemon/{$myPokemon->id}/battles")
            ->assertOk()
            ->assertJsonCount(0, 'data')
            ->assertJsonPath('summary.wins', 0)
            ->assertJsonPath('summary.losses', 0);
    }

    public function test_summary_counts_all_finished_battles_beyond_the_current_page(): void
    {
        $myPokemon = MyPokemon::factory()->create();
        $rival = MyPokemon::factory()->create();

        // 20 combates: 12 ganados, 8 perdidos. La página por defecto trae menos de 20.
        for ($i = 0; $i < 12; $i++) {
            $this->createBattle($myPokemon, $rival, BattleStatus::Finished, $myPokemon, sprintf('2024-02-01 %02d:00:00', $i));
        }
        for ($i = 0; $i < 8; $i++) {
            $this->createBattle($rival, $myPokemon, BattleStatus::Finished, $rival, sprintf('2024-02-02 %02d:00:00', $i));
        }

        $response = $this->getJson("/api/my-pokemon/{$myPokemon->id}/battles")
            ->assertOk()
            ->assertJsonPath('summary.wins', 12)
            ->assertJsonPath('summary.losses', 8)
            ->assertJsonPath('meta.total', 20);

        $this->assertLessThan(20, count($response->json('data')));
    }
}
