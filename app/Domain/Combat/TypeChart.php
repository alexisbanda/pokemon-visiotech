<?php

declare(strict_types=1);

namespace App\Domain\Combat;

/**
 * Tabla de efectividad de tipos (Gen 6+, incluye Fairy).
 *
 * El multiplicador es el del *tipo del movimiento* (ataque) contra el
 * *tipo del Pokémon defensor*. Solo se listan las relaciones distintas de ×1;
 * cualquier par no listado devuelve 1.0 (daño normal).
 *
 *  - ×2.0  → súper eficaz (debilidad)
 *  - ×0.5  → poco eficaz (resistencia)
 *  - ×0.0  → sin efecto (inmunidad)
 */
final class TypeChart
{
    public const float SUPER_EFFECTIVE = 2.0;

    public const float NOT_VERY_EFFECTIVE = 0.5;

    public const float NO_EFFECT = 0.0;

    public const float NEUTRAL = 1.0;

    /**
     * @return array<string, array<string, float>>
     */
    private static function chart(): array
    {
        $x2 = self::SUPER_EFFECTIVE;
        $half = self::NOT_VERY_EFFECTIVE;
        $x0 = self::NO_EFFECT;

        return [
            PokemonType::Normal->value => [
                PokemonType::Rock->value => $half,
                PokemonType::Steel->value => $half,
                PokemonType::Ghost->value => $x0,
            ],
            PokemonType::Fire->value => [
                PokemonType::Grass->value => $x2,
                PokemonType::Ice->value => $x2,
                PokemonType::Bug->value => $x2,
                PokemonType::Steel->value => $x2,
                PokemonType::Fire->value => $half,
                PokemonType::Water->value => $half,
                PokemonType::Rock->value => $half,
                PokemonType::Dragon->value => $half,
            ],
            PokemonType::Water->value => [
                PokemonType::Fire->value => $x2,
                PokemonType::Ground->value => $x2,
                PokemonType::Rock->value => $x2,
                PokemonType::Water->value => $half,
                PokemonType::Grass->value => $half,
                PokemonType::Dragon->value => $half,
            ],
            PokemonType::Electric->value => [
                PokemonType::Water->value => $x2,
                PokemonType::Flying->value => $x2,
                PokemonType::Electric->value => $half,
                PokemonType::Grass->value => $half,
                PokemonType::Dragon->value => $half,
                PokemonType::Ground->value => $x0,
            ],
            PokemonType::Grass->value => [
                PokemonType::Water->value => $x2,
                PokemonType::Ground->value => $x2,
                PokemonType::Rock->value => $x2,
                PokemonType::Fire->value => $half,
                PokemonType::Grass->value => $half,
                PokemonType::Poison->value => $half,
                PokemonType::Flying->value => $half,
                PokemonType::Bug->value => $half,
                PokemonType::Dragon->value => $half,
                PokemonType::Steel->value => $half,
            ],
            PokemonType::Ice->value => [
                PokemonType::Grass->value => $x2,
                PokemonType::Ground->value => $x2,
                PokemonType::Flying->value => $x2,
                PokemonType::Dragon->value => $x2,
                PokemonType::Fire->value => $half,
                PokemonType::Water->value => $half,
                PokemonType::Ice->value => $half,
                PokemonType::Steel->value => $half,
            ],
            PokemonType::Fighting->value => [
                PokemonType::Normal->value => $x2,
                PokemonType::Ice->value => $x2,
                PokemonType::Rock->value => $x2,
                PokemonType::Dark->value => $x2,
                PokemonType::Steel->value => $x2,
                PokemonType::Poison->value => $half,
                PokemonType::Flying->value => $half,
                PokemonType::Psychic->value => $half,
                PokemonType::Bug->value => $half,
                PokemonType::Fairy->value => $half,
                PokemonType::Ghost->value => $x0,
            ],
            PokemonType::Poison->value => [
                PokemonType::Grass->value => $x2,
                PokemonType::Fairy->value => $x2,
                PokemonType::Poison->value => $half,
                PokemonType::Ground->value => $half,
                PokemonType::Rock->value => $half,
                PokemonType::Ghost->value => $half,
                PokemonType::Steel->value => $x0,
            ],
            PokemonType::Ground->value => [
                PokemonType::Fire->value => $x2,
                PokemonType::Electric->value => $x2,
                PokemonType::Poison->value => $x2,
                PokemonType::Rock->value => $x2,
                PokemonType::Steel->value => $x2,
                PokemonType::Grass->value => $half,
                PokemonType::Bug->value => $half,
                PokemonType::Flying->value => $x0,
            ],
            PokemonType::Flying->value => [
                PokemonType::Grass->value => $x2,
                PokemonType::Fighting->value => $x2,
                PokemonType::Bug->value => $x2,
                PokemonType::Electric->value => $half,
                PokemonType::Rock->value => $half,
                PokemonType::Steel->value => $half,
            ],
            PokemonType::Psychic->value => [
                PokemonType::Fighting->value => $x2,
                PokemonType::Poison->value => $x2,
                PokemonType::Psychic->value => $half,
                PokemonType::Steel->value => $half,
                PokemonType::Dark->value => $x0,
            ],
            PokemonType::Bug->value => [
                PokemonType::Grass->value => $x2,
                PokemonType::Psychic->value => $x2,
                PokemonType::Dark->value => $x2,
                PokemonType::Fire->value => $half,
                PokemonType::Fighting->value => $half,
                PokemonType::Poison->value => $half,
                PokemonType::Flying->value => $half,
                PokemonType::Ghost->value => $half,
                PokemonType::Steel->value => $half,
                PokemonType::Fairy->value => $half,
            ],
            PokemonType::Rock->value => [
                PokemonType::Fire->value => $x2,
                PokemonType::Ice->value => $x2,
                PokemonType::Flying->value => $x2,
                PokemonType::Bug->value => $x2,
                PokemonType::Fighting->value => $half,
                PokemonType::Ground->value => $half,
                PokemonType::Steel->value => $half,
            ],
            PokemonType::Ghost->value => [
                PokemonType::Psychic->value => $x2,
                PokemonType::Ghost->value => $x2,
                PokemonType::Dark->value => $half,
                PokemonType::Normal->value => $x0,
            ],
            PokemonType::Dragon->value => [
                PokemonType::Dragon->value => $x2,
                PokemonType::Steel->value => $half,
                PokemonType::Fairy->value => $x0,
            ],
            PokemonType::Dark->value => [
                PokemonType::Psychic->value => $x2,
                PokemonType::Ghost->value => $x2,
                PokemonType::Fighting->value => $half,
                PokemonType::Dark->value => $half,
                PokemonType::Fairy->value => $half,
            ],
            PokemonType::Steel->value => [
                PokemonType::Ice->value => $x2,
                PokemonType::Rock->value => $x2,
                PokemonType::Fairy->value => $x2,
                PokemonType::Fire->value => $half,
                PokemonType::Water->value => $half,
                PokemonType::Electric->value => $half,
                PokemonType::Steel->value => $half,
            ],
            PokemonType::Fairy->value => [
                PokemonType::Fighting->value => $x2,
                PokemonType::Dragon->value => $x2,
                PokemonType::Dark->value => $x2,
                PokemonType::Fire->value => $half,
                PokemonType::Poison->value => $half,
                PokemonType::Steel->value => $half,
            ],
        ];
    }

    /**
     * Multiplicador de efectividad del tipo atacante contra el tipo defensor.
     */
    public static function multiplier(PokemonType $attack, PokemonType $defense): float
    {
        return self::chart()[$attack->value][$defense->value] ?? self::NEUTRAL;
    }

    /**
     * Tipos atacantes que son súper eficaces (×2) contra el tipo defensor dado,
     * ordenados alfabéticamente por su valor.
     *
     * @return list<PokemonType>
     */
    public static function weaknessesOf(PokemonType $defense): array
    {
        $weaknesses = array_values(array_filter(
            PokemonType::cases(),
            fn (PokemonType $attack): bool => self::multiplier($attack, $defense) === self::SUPER_EFFECTIVE,
        ));

        usort($weaknesses, fn (PokemonType $a, PokemonType $b): int => $a->value <=> $b->value);

        return $weaknesses;
    }
}
