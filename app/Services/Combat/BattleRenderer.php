<?php

declare(strict_types=1);

namespace App\Services\Combat;

use App\Domain\Combat\DamageResult;
use App\Enums\BattleSide;
use App\Models\Battle;
use App\Models\BattleTurn;
use Illuminate\Console\OutputStyle;

/**
 * Presentación por consola de un combate (crónica vertical). Solo pinta; no
 * conoce la lógica de combate. Separarlo del BattleService permite testear el
 * comando sobre texto plano y cambiar la estética sin tocar el motor.
 */
final class BattleRenderer
{
    private const SEGMENTS = 10;

    public function __construct(
        private readonly OutputStyle $output,
        private readonly bool $ascii = false,
    ) {}

    public function header(Battle $battle, ?int $seed): void
    {
        $sword = $this->ascii ? '' : '⚔  ';
        $seedTag = $seed !== null ? "   <fg=gray>seed {$seed}</>" : '';

        $this->output->newLine();
        $this->output->writeln("  <options=bold>{$sword}POKÉMON BATTLE</>{$seedTag}");
        $this->output->writeln('  '.$this->combatantLine($battle, BattleSide::First));
        $this->output->writeln('  '.$this->combatantLine($battle, BattleSide::Second));
        $this->output->newLine();
        $this->scoreboard($battle);
        $this->output->newLine();
    }

    public function turn(Battle $battle, BattleTurn $turn, string $moveName): void
    {
        $attacker = $battle->combatantOn($turn->attacker);
        $defender = $battle->combatantOn($turn->attacker->opponent());

        $this->output->writeln('  <fg=gray>── Turno '.$turn->number.' '.str_repeat('─', 46).'</>');
        $this->output->writeln(sprintf(
            '   %s usa <fg=cyan>%s</>.  %s<fg=red>%d</> de daño a %s.',
            $attacker->nickname,
            $moveName,
            $this->effectivenessNote($turn->label),
            $turn->damage,
            $defender->nickname,
        ));

        if ($turn->defender_hp_after <= 0) {
            $this->output->writeln("   <fg=yellow>{$defender->nickname} se debilita.</>");
        }

        $this->output->newLine();
        $this->scoreboard($battle);
        $this->output->newLine();
    }

    public function result(Battle $battle): void
    {
        $boom = $this->ascii ? '' : '💥 ';
        $winner = $battle->winner;
        $turnWord = $battle->turn_number === 1 ? 'turno' : 'turnos';

        $this->output->writeln('  <fg=gray>'.str_repeat('─', 58).'</>');
        $this->output->writeln(sprintf(
            '  %s<options=bold;fg=green>¡Gana %s!</>  <fg=gray>(%d %s)</>',
            $boom,
            $winner?->nickname ?? '—',
            $battle->turn_number,
            $turnWord,
        ));
        $this->output->newLine();
    }

    /**
     * Marcador con las dos barras de PS (el "tablero").
     */
    private function scoreboard(Battle $battle): void
    {
        foreach ([BattleSide::First, BattleSide::Second] as $side) {
            $mp = $battle->combatantOn($side);
            $this->output->writeln(sprintf(
                '   %-13s %s',
                $mp->nickname,
                $this->hpBar($battle->currentHp($side), $mp->maxHp()),
            ));
        }
    }

    private function combatantLine(Battle $battle, BattleSide $side): string
    {
        $mp = $battle->combatantOn($side);
        $first = $battle->turn === $side
            ? ($this->ascii ? '  <- primero' : '   <fg=cyan>⟵ primero</>')
            : '';

        return sprintf(
            '%-13s Lv%-3d %-10s %-9s SPD %-3d%s',
            $mp->nickname,
            $mp->level,
            $mp->pokemon->name,
            $mp->pokemon->type->value,
            $mp->pokemon->speed,
            $first,
        );
    }

    private function hpBar(int $current, int $max): string
    {
        $pct = $max > 0 ? $current / $max : 0.0;
        $filled = $current <= 0 ? 0 : (int) max(1, ceil($pct * self::SEGMENTS));
        $color = $pct > 0.5 ? 'green' : ($pct > 0.2 ? 'yellow' : 'red');

        $bar = str_repeat('▰', $filled).str_repeat('▱', self::SEGMENTS - $filled);

        return "<fg={$color}>{$bar}</> ".sprintf('%3d/%-3d', $current, $max);
    }

    private function effectivenessNote(string $label): string
    {
        return match ($label) {
            DamageResult::LABEL_SUPER_EFFECTIVE => $this->ascii ? '(supereficaz) ' : '⚡ ¡Supereficaz!  ',
            DamageResult::LABEL_NOT_VERY_EFFECTIVE => $this->ascii ? '(poco eficaz) ' : '🛡  Poco eficaz…  ',
            DamageResult::LABEL_NO_EFFECT => $this->ascii ? '(sin efecto) ' : '✋ Sin efecto.  ',
            default => '',
        };
    }
}
