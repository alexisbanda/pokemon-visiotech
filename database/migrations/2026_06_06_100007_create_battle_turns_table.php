<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Log auditable de cada fase del combate (para GET /battles/{id} y para el
     * comando battle:simulate).
     */
    public function up(): void
    {
        Schema::create('battle_turns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('battle_id')->constrained('battles')->cascadeOnDelete();
            $table->unsignedInteger('number');
            $table->string('attacker'); // BattleSide que atacó
            $table->foreignId('move_id')->constrained('moves')->cascadeOnDelete();
            $table->unsignedSmallInteger('damage');
            $table->decimal('effectiveness', 4, 2);
            $table->string('label'); // "super effective" / "normal" / ...
            $table->unsignedSmallInteger('defender_hp_after');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('battle_turns');
    }
};
