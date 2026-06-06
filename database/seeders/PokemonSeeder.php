<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Move;
use App\Models\Pokemon;
use Illuminate\Database\Seeder;

/**
 * Pokémon base (stats reales de pokemondb.net) y sus movimientos *posibles*.
 * Varios movimientos se comparten entre especies a propósito, para que la
 * consulta "Pokémon que comparten un movimiento" devuelva resultados.
 */
class PokemonSeeder extends Seeder
{
    /**
     * @var array<string, array{type: string, stats: array<string, int>, moves: list<string>}>
     */
    public const POKEMON = [
        'Charizard' => [
            'type' => 'fire',
            'stats' => ['hp' => 78, 'attack' => 84, 'defense' => 78, 'sp_attack' => 109, 'sp_defense' => 85, 'speed' => 100],
            'moves' => ['Flamethrower', 'Fire Punch', 'Dragon Claw', 'Earthquake', 'Body Slam', 'Hyper Beam'],
        ],
        'Blastoise' => [
            'type' => 'water',
            'stats' => ['hp' => 79, 'attack' => 83, 'defense' => 100, 'sp_attack' => 85, 'sp_defense' => 105, 'speed' => 78],
            'moves' => ['Surf', 'Hydro Pump', 'Ice Beam', 'Earthquake', 'Body Slam'],
        ],
        'Venusaur' => [
            'type' => 'grass',
            'stats' => ['hp' => 80, 'attack' => 82, 'defense' => 83, 'sp_attack' => 100, 'sp_defense' => 100, 'speed' => 80],
            'moves' => ['Razor Leaf', 'Solar Beam', 'Sludge Bomb', 'Body Slam'],
        ],
        'Pikachu' => [
            'type' => 'electric',
            'stats' => ['hp' => 35, 'attack' => 55, 'defense' => 40, 'sp_attack' => 50, 'sp_defense' => 50, 'speed' => 90],
            'moves' => ['Thunderbolt', 'Thunder Punch', 'Body Slam', 'Tackle'],
        ],
        'Gengar' => [
            'type' => 'ghost',
            'stats' => ['hp' => 60, 'attack' => 65, 'defense' => 60, 'sp_attack' => 130, 'sp_defense' => 75, 'speed' => 110],
            'moves' => ['Shadow Ball', 'Sludge Bomb', 'Thunderbolt', 'Dynamic Punch'],
        ],
        'Machamp' => [
            'type' => 'fighting',
            'stats' => ['hp' => 90, 'attack' => 130, 'defense' => 80, 'sp_attack' => 65, 'sp_defense' => 85, 'speed' => 55],
            'moves' => ['Cross Chop', 'Dynamic Punch', 'Thunder Punch', 'Fire Punch', 'Earthquake', 'Body Slam'],
        ],
        'Dragonite' => [
            'type' => 'dragon',
            'stats' => ['hp' => 91, 'attack' => 134, 'defense' => 95, 'sp_attack' => 100, 'sp_defense' => 100, 'speed' => 80],
            'moves' => ['Dragon Claw', 'Outrage', 'Thunderbolt', 'Ice Beam', 'Earthquake', 'Body Slam', 'Hyper Beam'],
        ],
        'Snorlax' => [
            'type' => 'normal',
            'stats' => ['hp' => 160, 'attack' => 110, 'defense' => 65, 'sp_attack' => 65, 'sp_defense' => 110, 'speed' => 30],
            'moves' => ['Body Slam', 'Hyper Beam', 'Earthquake', 'Mega Punch', 'Tackle'],
        ],
    ];

    public function run(): void
    {
        $moveIds = Move::pluck('id', 'name');

        foreach (self::POKEMON as $name => $data) {
            $pokemon = Pokemon::updateOrCreate(
                ['name' => $name],
                ['type' => $data['type'], ...$data['stats']],
            );

            $pokemon->moves()->sync(
                collect($data['moves'])->map(fn (string $move) => $moveIds[$move])->all(),
            );
        }
    }
}
