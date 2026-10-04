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

    public function test_weaknesses_of_fire_are_ground_rock_and_water(): void
    {
        $this->assertSame(
            [PokemonType::Ground, PokemonType::Rock, PokemonType::Water],
            TypeChart::weaknessesOf(PokemonType::Fire),
        );
    }

    public function test_weaknesses_of_normal_is_only_fighting(): void
    {
        $this->assertSame(
            [PokemonType::Fighting],
            TypeChart::weaknessesOf(PokemonType::Normal),
        );
    }

    /**
     * Ghost es inmune a Normal y a Fighting (×0), así que ninguno de los dos
     * debe aparecer como debilidad. Sus únicas debilidades reales (×2) son
     * Dark y Ghost.
     */
    public function test_weaknesses_of_ghost_excludes_its_immunities(): void
    {
        $weaknesses = TypeChart::weaknessesOf(PokemonType::Ghost);

        $this->assertSame([PokemonType::Dark, PokemonType::Ghost], $weaknesses);
        $this->assertNotContains(PokemonType::Normal, $weaknesses);
        $this->assertNotContains(PokemonType::Fighting, $weaknesses);
    }

    /**
     * Verifica weaknessesOf contra la propia tabla (chart()), recorriéndola
     * en lugar de confiar en una lista escrita de memoria.
     */
    public function test_weaknesses_of_every_type_match_the_chart_super_effective_entries(): void
    {
        foreach (PokemonType::cases() as $defense) {
            $expected = [];
            foreach (PokemonType::cases() as $attack) {
                if (TypeChart::multiplier($attack, $defense) === TypeChart::SUPER_EFFECTIVE) {
                    $expected[] = $attack;
                }
            }

            usort($expected, fn (PokemonType $a, PokemonType $b): int => $a->value <=> $b->value);

            $this->assertSame($expected, TypeChart::weaknessesOf($defense), "Debilidades de {$defense->value}");
        }
    }
}
