<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Combat;

use App\Domain\Combat\PokemonType;
use App\Domain\Combat\TypeChart;
use PHPUnit\Framework\TestCase;

final class TypeChartTest extends TestCase
{
    public function test_super_effective_relationships(): void
    {
        $this->assertSame(2.0, TypeChart::multiplier(PokemonType::Water, PokemonType::Fire));
        $this->assertSame(2.0, TypeChart::multiplier(PokemonType::Fairy, PokemonType::Dragon));
        $this->assertSame(2.0, TypeChart::multiplier(PokemonType::Ground, PokemonType::Steel));
    }

    public function test_not_very_effective_relationships(): void
    {
        $this->assertSame(0.5, TypeChart::multiplier(PokemonType::Fire, PokemonType::Water));
        $this->assertSame(0.5, TypeChart::multiplier(PokemonType::Normal, PokemonType::Rock));
        $this->assertSame(0.5, TypeChart::multiplier(PokemonType::Steel, PokemonType::Fire));
    }

    public function test_neutral_when_relationship_not_listed(): void
    {
        $this->assertSame(1.0, TypeChart::multiplier(PokemonType::Normal, PokemonType::Water));
        $this->assertSame(1.0, TypeChart::multiplier(PokemonType::Fire, PokemonType::Electric));
    }

    /**
     * Las 8 inmunidades (×0) de la tabla Gen 6+, verificadas contra el PDF.
     */
    public function test_all_immunities_are_zero(): void
    {
        $immunities = [
            [PokemonType::Normal, PokemonType::Ghost],
            [PokemonType::Fighting, PokemonType::Ghost],
            [PokemonType::Poison, PokemonType::Steel],
            [PokemonType::Ground, PokemonType::Flying],
            [PokemonType::Ghost, PokemonType::Normal],
            [PokemonType::Electric, PokemonType::Ground],
            [PokemonType::Psychic, PokemonType::Dark],
            [PokemonType::Dragon, PokemonType::Fairy],
        ];

        foreach ($immunities as [$attack, $defense]) {
            $this->assertSame(
                0.0,
                TypeChart::multiplier($attack, $defense),
                "{$attack->value} debería ser inmune contra {$defense->value}",
            );
        }
    }
}
