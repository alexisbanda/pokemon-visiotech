<?php

declare(strict_types=1);

namespace App\Domain\Combat;

/**
 * Los 18 tipos de Pokémon (Gen 6+, incluye Fairy).
 * Identificadores en inglés por convención estándar.
 */
enum PokemonType: string
{
    case Normal = 'normal';
    case Fire = 'fire';
    case Water = 'water';
    case Electric = 'electric';
    case Grass = 'grass';
    case Ice = 'ice';
    case Fighting = 'fighting';
    case Poison = 'poison';
    case Ground = 'ground';
    case Flying = 'flying';
    case Psychic = 'psychic';
    case Bug = 'bug';
    case Rock = 'rock';
    case Ghost = 'ghost';
    case Dragon = 'dragon';
    case Dark = 'dark';
    case Steel = 'steel';
    case Fairy = 'fairy';
}
