<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Combat\Random\RandomFactor;
use App\Enums\BattleSide;
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
        {first? : Id del MyPokemon que pelea en primer lugar (si se omite, lo eliges en un menú)}
        {second? : Id del MyPokemon rival (si se omite, lo eliges en un menú)}
        {--seed= : Semilla para un combate reproducible}
        {--interactive : Elegir los movimientos a mano}
        {--no-delay : Sin pausa entre turnos (CI/tests)}
        {--ascii : Salida sin emojis}';

    protected $description = 'Simula un combate entre dos MyPokemon e imprime la crónica turno a turno.';

    public function handle(): int
    {
        $first = $this->resolveCombatant($this->argument('first'), 'Elige el primer combatiente', null);
        if ($first === null) {
            return self::FAILURE;
        }

        $second = $this->resolveCombatant($this->argument('second'), 'Elige el rival', $first->id);
        if ($second === null) {
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

        // En modo interactivo controlas el primer combatiente; la CPU juega el otro.
        $interactive = (bool) $this->option('interactive');
        if ($interactive) {
            $this->line("  <fg=cyan>Controlas a {$first->nickname}.</> La CPU juega con {$second->nickname}.");
            $this->newLine();
        }

        $delay = ! $this->option('no-delay') && ! $interactive;
        $maxTurns = 500; // salvaguarda: evita un bucle infinito si nadie hace daño

        while (! $battle->isFinished() && $battle->turn_number < $maxTurns) {
            $side = $battle->turn;
            $attacker = $battle->combatantOn($side);
            $humanTurn = $interactive && $side === BattleSide::First;

            $move = $humanTurn ? $this->promptMove($attacker) : $this->randomMove($attacker);

            $turn = $service->takeTurn($battle, $move);
            $renderer->turn($battle, $turn, $move->name);

            if ($delay && ! $battle->isFinished()) {
                usleep(600_000);
            }
        }

        if (! $battle->isFinished()) {
            $this->warn("  El combate no terminó en {$maxTurns} turnos (ningún bando logró debilitar al otro).");

            return self::SUCCESS;
        }

        $renderer->result($battle->load('winner'));

        return self::SUCCESS;
    }

    /**
     * Resuelve un combatiente: por id si se pasó como argumento, o con un menú
     * de selección si se omitió. `$excludeId` evita ofrecer el ya elegido.
     */
    private function resolveCombatant(int|string|null $id, string $label, ?int $excludeId): ?MyPokemon
    {
        if ($id !== null) {
            $myPokemon = MyPokemon::with(['pokemon', 'moves'])->find($id);

            if ($myPokemon === null) {
                $this->error("No existe ningún MyPokemon con id {$id}.");
            }

            return $myPokemon;
        }

        $candidates = MyPokemon::with('pokemon')
            ->when($excludeId, fn ($query) => $query->whereKeyNot($excludeId))
            ->orderBy('nickname')
            ->get();

        if ($candidates->isEmpty()) {
            $this->error('No hay MyPokemon disponibles. Ejecuta «artisan migrate --seed».');

            return null;
        }

        $options = $candidates
            ->mapWithKeys(fn (MyPokemon $m) => [$m->id => "{$m->nickname} — {$m->pokemon->name} (Lv{$m->level})"])
            ->all();

        $choice = $this->choice($label, $options);

        // choice() puede devolver la clave (id) o la etiqueta según la versión;
        // resolvemos el id de ambos modos.
        $id = array_key_exists($choice, $options) ? $choice : array_search($choice, $options, true);

        return MyPokemon::with(['pokemon', 'moves'])->find((int) $id);
    }

    /**
     * Elección automática (CPU): movimiento al azar. Usa mt_rand (no random_int)
     * sobre una lista ordenada por id para respetar la semilla de --seed.
     */
    private function randomMove(MyPokemon $attacker): Move
    {
        $moves = $attacker->moves->sortBy('id')->values();

        return $moves[mt_rand(0, $moves->count() - 1)];
    }

    private function promptMove(MyPokemon $attacker): Move
    {
        $choice = select(
            label: "▶ Tu turno ({$attacker->nickname}) — elige movimiento",
            options: $attacker->moves
                ->mapWithKeys(fn (Move $m) => [$m->id => "{$m->name}  ({$m->type->value}, poder {$m->power})"])
                ->all(),
        );

        return $attacker->moves->firstWhere('id', (int) $choice);
    }
}
