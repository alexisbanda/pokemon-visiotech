<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Move;
use Illuminate\Database\Seeder;

/**
 * Movimientos reales (poder y tipo según pokemondb.net). Suficientes para
 * cubrir las consultas y los combates de demostración.
 */
class MoveSeeder extends Seeder
{
    /**
     * @var list<array{name: string, power: int, type: string}>
     */
    public const MOVES = [
        ['name' => 'Tackle', 'power' => 40, 'type' => 'normal'],
        ['name' => 'Body Slam', 'power' => 85, 'type' => 'normal'],
        ['name' => 'Mega Punch', 'power' => 80, 'type' => 'normal'],
        ['name' => 'Hyper Beam', 'power' => 150, 'type' => 'normal'],
        ['name' => 'Flamethrower', 'power' => 90, 'type' => 'fire'],
        ['name' => 'Fire Punch', 'power' => 75, 'type' => 'fire'],
        ['name' => 'Surf', 'power' => 90, 'type' => 'water'],
        ['name' => 'Hydro Pump', 'power' => 110, 'type' => 'water'],
        ['name' => 'Thunderbolt', 'power' => 90, 'type' => 'electric'],
        ['name' => 'Thunder Punch', 'power' => 75, 'type' => 'electric'],
        ['name' => 'Razor Leaf', 'power' => 55, 'type' => 'grass'],
        ['name' => 'Solar Beam', 'power' => 120, 'type' => 'grass'],
        ['name' => 'Ice Beam', 'power' => 90, 'type' => 'ice'],
        ['name' => 'Cross Chop', 'power' => 100, 'type' => 'fighting'],
        ['name' => 'Dynamic Punch', 'power' => 100, 'type' => 'fighting'],
        ['name' => 'Earthquake', 'power' => 100, 'type' => 'ground'],
        ['name' => 'Sludge Bomb', 'power' => 90, 'type' => 'poison'],
        ['name' => 'Shadow Ball', 'power' => 80, 'type' => 'ghost'],
        ['name' => 'Dragon Claw', 'power' => 80, 'type' => 'dragon'],
        ['name' => 'Outrage', 'power' => 120, 'type' => 'dragon'],
    ];

    public function run(): void
    {
        foreach (self::MOVES as $move) {
            Move::updateOrCreate(['name' => $move['name']], $move);
        }
    }
}
