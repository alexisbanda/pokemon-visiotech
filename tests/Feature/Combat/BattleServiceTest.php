<?php

declare(strict_types=1);

namespace Tests\Feature\Combat;

use App\Domain\Combat\PokemonType;
use App\Domain\Combat\Random\FixedRandomFactor;
use App\Domain\Combat\Random\RandomFactor;
use App\Enums\BattleSide;
use App\Enums\BattleStatus;
use App\Exceptions\BattleAlreadyFinishedException;
use App\Exceptions\InvalidMoveException;
use App\Models\Move;
use App\Models\MyPokemon;
use App\Models\Pokemon;
use App\Services\Combat\BattleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class BattleServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Combate determinista: el factor aleatorio siempre 100.
        $this->app->instance(RandomFactor::class, new FixedRandomFactor(100));
    }

    private function service(): BattleService
    {
        return $this->app->make(BattleService::class);
    }

    /**
     * @param  array<string, int>  $stats
     * @param  list<array{name: string, power: int, type: PokemonType}>  $moves
     */
    private function makeMyPokemon(array $stats, PokemonType $type, array $moves = []): MyPokemon
    {
        $pokemon = Pokemon::factory()->create([...$stats, 'type' => $type]);
        $moveModels = collect($moves)->map(fn (array $m) => Move::factory()->create($m));
        $pokemon->moves()->attach($moveModels->pluck('id'));

        $myPokemon = MyPokemon::factory()->create(['pokemon_id' => $pokemon->id, 'level' => 50]);
        $myPokemon->moves()->sync($moveModels->pluck('id'));

        return $myPokemon->load(['pokemon', 'moves']);
    }

    /**
     * @param  array<string, int>  $overrides
     * @return array<string, int>
     */
    private function stats(array $overrides = []): array
    {
        return [...['hp' => 100, 'attack' => 100, 'defense' => 50, 'sp_attack' => 50, 'sp_defense' => 50, 'speed' => 50], ...$overrides];
    }

    public function test_create_gives_first_turn_to_the_faster_combatant(): void
    {
        $slow = $this->makeMyPokemon($this->stats(['speed' => 40]), PokemonType::Water);
        $fast = $this->makeMyPokemon($this->stats(['speed' => 99]), PokemonType::Fire);

        $battle = $this->service()->create($slow, $fast);

        $this->assertSame(BattleSide::Second, $battle->turn); // el rápido es el "second"
        $this->assertSame(BattleStatus::InProgress, $battle->status);
    }

    public function test_create_breaks_speed_tie_in_favor_of_first(): void
    {
        $a = $this->makeMyPokemon($this->stats(['speed' => 60]), PokemonType::Water);
        $b = $this->makeMyPokemon($this->stats(['speed' => 60]), PokemonType::Fire);

        $this->assertSame(BattleSide::First, $this->service()->create($a, $b)->turn);
    }

    public function test_create_initializes_hp_from_species(): void
    {
        $a = $this->makeMyPokemon($this->stats(['hp' => 78]), PokemonType::Fire);
        $b = $this->makeMyPokemon($this->stats(['hp' => 120]), PokemonType::Water);

        $battle = $this->service()->create($a, $b);

        $this->assertSame(78, $battle->first_current_hp);
        $this->assertSame(120, $battle->second_current_hp);
    }

    public function test_turn_applies_super_effective_damage_and_flips_turn(): void
    {
        // attacker: level 50, attack 100, Water; defender: defense 50, Fire, hp 200
        $attacker = $this->makeMyPokemon(
            $this->stats(['attack' => 100, 'speed' => 99]),
            PokemonType::Water,
            [['name' => 'Surf', 'power' => 80, 'type' => PokemonType::Water]],
        );
        $defender = $this->makeMyPokemon($this->stats(['defense' => 50, 'hp' => 200]), PokemonType::Fire);

        $battle = $this->service()->create($attacker, $defender);
        $move = $attacker->moves->first();

        $turn = $this->service()->takeTurn($battle, $move);

        // base = floor(22*100*80/50/50)=70 ; damage = floor(70*2.0)=140
        $this->assertSame(140, $turn->damage);
        $this->assertSame(2.0, $turn->effectiveness);
        $this->assertSame('super effective', $turn->label);

        $battle->refresh();
        $this->assertSame(60, $battle->second_current_hp); // 200 - 140
        $this->assertSame(BattleSide::Second, $battle->turn); // pasa al defensor
        $this->assertSame(BattleStatus::InProgress, $battle->status);
    }

    public function test_battle_finishes_when_defender_faints(): void
    {
        $attacker = $this->makeMyPokemon(
            $this->stats(['attack' => 100, 'speed' => 99]),
            PokemonType::Water,
            [['name' => 'Surf', 'power' => 80, 'type' => PokemonType::Water]],
        );
        $defender = $this->makeMyPokemon($this->stats(['defense' => 50, 'hp' => 40]), PokemonType::Fire);

        $battle = $this->service()->create($attacker, $defender);
        $turn = $this->service()->takeTurn($battle, $attacker->moves->first());

        $this->assertSame(0, $turn->defender_hp_after);

        $battle->refresh();
        $this->assertSame(BattleStatus::Finished, $battle->status);
        $this->assertSame($attacker->id, $battle->winner_my_pokemon_id);
        $this->assertSame(0, $battle->second_current_hp);
    }

    public function test_taking_a_turn_on_a_finished_battle_throws(): void
    {
        $attacker = $this->makeMyPokemon(
            $this->stats(['attack' => 100, 'speed' => 99]),
            PokemonType::Water,
            [['name' => 'Surf', 'power' => 80, 'type' => PokemonType::Water]],
        );
        $defender = $this->makeMyPokemon($this->stats(['defense' => 50, 'hp' => 40]), PokemonType::Fire);

        $battle = $this->service()->create($attacker, $defender);
        $this->service()->takeTurn($battle, $attacker->moves->first()); // KO → finished

        $this->expectException(BattleAlreadyFinishedException::class);
        $this->service()->takeTurn($battle->refresh(), $attacker->moves->first());
    }

    public function test_taking_a_turn_with_a_foreign_move_throws(): void
    {
        $attacker = $this->makeMyPokemon(
            $this->stats(['speed' => 99]),
            PokemonType::Water,
            [['name' => 'Surf', 'power' => 80, 'type' => PokemonType::Water]],
        );
        $defender = $this->makeMyPokemon($this->stats(), PokemonType::Fire);
        $foreign = Move::factory()->create(); // no pertenece al atacante

        $battle = $this->service()->create($attacker, $defender);

        $this->expectException(InvalidMoveException::class);
        $this->service()->takeTurn($battle, $foreign);
    }
}
