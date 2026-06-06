<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Combat\Random\RandomFactor;
use App\Models\Move;
use App\Models\MyPokemon;
use App\Services\Combat\BattleRenderer;
use App\Services\Combat\BattleService;
use App\Services\Combat\SeededRandomFactor;
use Illuminate\Console\Command;

use function Laravel\Prompts\select;

class SimulateBattleCommand extends Command
{
    protected $signature = 'battle:simulate
        {first : Id del MyPokemon que pelea en primer lugar}
        {second : Id del MyPokemon rival}
        {--seed= : Semilla para un combate reproducible}
        {--interactive : Elegir los movimientos a mano}
        {--no-delay : Sin pausa entre turnos (CI/tests)}
        {--ascii : Salida sin emojis}';

    protected $description = 'Simula un combate entre dos MyPokemon e imprime la crónica turno a turno.';

    public function handle(): int
    {
        $first = MyPokemon::with(['pokemon', 'moves'])->find($this->argument('first'));
        $second = MyPokemon::with(['pokemon', 'moves'])->find($this->argument('second'));

        if ($first === null || $second === null) {
            $this->error('Alguno de los MyPokemon indicados no existe.');

            return self::FAILURE;
        }

        if ($first->is($second)) {
            $this->error('Un Pokémon no puede combatir contra sí mismo.');

            return self::FAILURE;
        }

        foreach ([$first, $second] as $combatant) {
            if ($combatant->moves->isEmpty()) {
                $this->error("{$combatant->nickname} no tiene movimientos y no puede combatir.");

                return self::FAILURE;
            }
        }

        $seed = $this->option('seed');
        if ($seed !== null) {
            // El daño usa SeededRandomFactor; la elección de movimiento usa el RNG
            // global (Collection::random) → se siembra también para reproducir todo.
            $this->laravel->instance(RandomFactor::class, new SeededRandomFactor((int) $seed));
            mt_srand((int) $seed);
        }

        $service = $this->laravel->make(BattleService::class);
        $renderer = new BattleRenderer($this->output, (bool) $this->option('ascii'));

        $battle = $service->create($first, $second);
        $battle->load([
            'firstMyPokemon.pokemon', 'firstMyPokemon.moves',
            'secondMyPokemon.pokemon', 'secondMyPokemon.moves',
        ]);

        $renderer->header($battle, $seed !== null ? (int) $seed : null);

        $interactive = (bool) $this->option('interactive');
        $delay = ! $this->option('no-delay');

        while (! $battle->isFinished()) {
            $attacker = $battle->combatantOn($battle->turn);
            $move = $this->chooseMove($attacker, $interactive);

            $turn = $service->takeTurn($battle, $move);
            $renderer->turn($battle, $turn, $move->name);

            if ($delay && ! $battle->isFinished()) {
                usleep(600_000);
            }
        }

        $renderer->result($battle->load('winner'));

        return self::SUCCESS;
    }

    private function chooseMove(MyPokemon $attacker, bool $interactive): Move
    {
        if (! $interactive) {
            // mt_rand (no random_int) para respetar la semilla de --seed; sobre
            // una lista ordenada por id para que el orden sea estable.
            $moves = $attacker->moves->sortBy('id')->values();

            return $moves[mt_rand(0, $moves->count() - 1)];
        }

        $choice = select(
            label: "Turno de {$attacker->nickname} — elige movimiento",
            options: $attacker->moves
                ->mapWithKeys(fn (Move $m) => [$m->id => "{$m->name} ({$m->type->value}, poder {$m->power})"])
                ->all(),
        );

        return $attacker->moves->firstWhere('id', (int) $choice);
    }
}
