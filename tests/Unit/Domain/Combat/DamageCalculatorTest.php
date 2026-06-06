<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Combat;

use App\Domain\Combat\Combatant;
use App\Domain\Combat\DamageCalculator;
use App\Domain\Combat\DamageResult;
use App\Domain\Combat\Move;
use App\Domain\Combat\PokemonType;
use App\Domain\Combat\Random\FixedRandomFactor;
use App\Domain\Combat\Stats;
use PHPUnit\Framework\TestCase;

final class DamageCalculatorTest extends TestCase
{
    private function attacker(): Combatant
    {
        // level 50, attack 100 → base = floor(22 * 100 * 80 / 100 / 50) = 35
        return new Combatant(
            name: 'Attacker',
            level: 50,
            type: PokemonType::Water,
            stats: new Stats(hp: 100, attack: 100, defense: 50, spAttack: 50, spDefense: 50, speed: 50),
            currentHp: 100,
        );
    }

    private function defender(PokemonType $type): Combatant
    {
        return new Combatant(
            name: 'Defender',
            level: 50,
            type: $type,
            stats: new Stats(hp: 100, attack: 50, defense: 100, spAttack: 50, spDefense: 50, speed: 50),
            currentHp: 100,
        );
    }

    private function calculator(int $random): DamageCalculator
    {
        return new DamageCalculator(new FixedRandomFactor($random));
    }

    public function test_neutral_effectiveness_with_max_random(): void
    {
        $move = new Move('Tackle', 80, PokemonType::Normal); // Normal vs Water = ×1
        $result = $this->calculator(100)->calculate($this->attacker(), $move, $this->defender(PokemonType::Water));

        $this->assertSame(35, $result->damage);
        $this->assertSame(1.0, $result->effectivenessMultiplier);
        $this->assertSame(DamageResult::LABEL_NORMAL, $result->label);
    }

    public function test_super_effective_doubles_damage(): void
    {
        $move = new Move('Surf', 80, PokemonType::Water); // Water vs Fire = ×2
        $result = $this->calculator(100)->calculate($this->attacker(), $move, $this->defender(PokemonType::Fire));

        $this->assertSame(70, $result->damage);
        $this->assertSame(2.0, $result->effectivenessMultiplier);
        $this->assertSame(DamageResult::LABEL_SUPER_EFFECTIVE, $result->label);
    }

    public function test_not_very_effective_halves_damage(): void
    {
        $move = new Move('Ember', 80, PokemonType::Fire); // Fire vs Water = ×0.5
        $result = $this->calculator(100)->calculate($this->attacker(), $move, $this->defender(PokemonType::Water));

        $this->assertSame(17, $result->damage); // floor(35 * 0.5) = floor(17.5)
        $this->assertSame(0.5, $result->effectivenessMultiplier);
        $this->assertSame(DamageResult::LABEL_NOT_VERY_EFFECTIVE, $result->label);
    }

    public function test_no_effect_deals_zero_damage(): void
    {
        $move = new Move('Thunderbolt', 80, PokemonType::Electric); // Electric vs Ground = ×0
        $result = $this->calculator(100)->calculate($this->attacker(), $move, $this->defender(PokemonType::Ground));

        $this->assertSame(0, $result->damage);
        $this->assertSame(0.0, $result->effectivenessMultiplier);
        $this->assertSame(DamageResult::LABEL_NO_EFFECT, $result->label);
    }

    public function test_min_random_applies_lower_bound(): void
    {
        $move = new Move('Tackle', 80, PokemonType::Normal);
        $result = $this->calculator(85)->calculate($this->attacker(), $move, $this->defender(PokemonType::Water));

        $this->assertSame(29, $result->damage); // floor(35 * 85 / 100) = floor(29.75)
    }

    public function test_random_factor_bounds_a_15_percent_spread(): void
    {
        $move = new Move('Tackle', 80, PokemonType::Normal);
        $attacker = $this->attacker();
        $defender = $this->defender(PokemonType::Water);

        $max = $this->calculator(100)->calculate($attacker, $move, $defender)->damage;
        $min = $this->calculator(85)->calculate($attacker, $move, $defender)->damage;

        $this->assertSame(35, $max);
        $this->assertSame(29, $min);
        $this->assertLessThanOrEqual($max, $min);
        // El mínimo no puede caer por debajo del 85% del máximo (±15%).
        $this->assertGreaterThanOrEqual((int) floor($max * 0.85), $min);
    }
}
