<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Move;
use App\Models\MyPokemon;
use App\Models\Pokemon;
use Illuminate\Database\Seeder;

/**
 * Instancias listas para combatir (cada una con 4 movimientos elegidos de entre
 * los posibles de su especie). Sirven de entrada para la API de combate (P3).
 */
class MyPokemonSeeder extends Seeder
{
    /**
     * @var list<array{nickname: string, species: string, level: int, moves: list<string>}>
     */
    public const ROSTER = [
        [
            'nickname' => 'Reptcomputer',
            'species' => 'Charizard',
            'level' => 50,
            'moves' => ['Flamethrower', 'Dragon Claw', 'Earthquake', 'Body Slam'],
        ],
        [
            'nickname' => 'Shellshock',
            'species' => 'Blastoise',
            'level' => 50,
            'moves' => ['Hydro Pump', 'Ice Beam', 'Earthquake', 'Body Slam'],
        ],
        [
            'nickname' => 'Sparky',
            'species' => 'Pikachu',
            'level' => 50,
            'moves' => ['Thunderbolt', 'Thunder Punch', 'Body Slam', 'Tackle'],
        ],
    ];

    public function run(): void
    {
        $moveIds = Move::pluck('id', 'name');

        foreach (self::ROSTER as $entry) {
            $pokemon = Pokemon::where('name', $entry['species'])->firstOrFail();

            $myPokemon = MyPokemon::updateOrCreate(
                ['nickname' => $entry['nickname']],
                ['pokemon_id' => $pokemon->id, 'level' => $entry['level']],
            );

            $myPokemon->moves()->sync(
                collect($entry['moves'])->map(fn (string $move) => $moveIds[$move])->all(),
            );
        }
    }
}
