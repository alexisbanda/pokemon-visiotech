<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Estado persistido de un combate por turnos entre dos MyPokemon.
     * Los PS actuales viven aquí (estado por partida), no en my_pokemon.
     */
    public function up(): void
    {
        Schema::create('battles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('first_my_pokemon_id')->constrained('my_pokemon')->cascadeOnDelete();
            $table->foreignId('second_my_pokemon_id')->constrained('my_pokemon')->cascadeOnDelete();
            $table->unsignedSmallInteger('first_current_hp');
            $table->unsignedSmallInteger('second_current_hp');
            $table->string('turn')->default('first');   // BattleSide: a quién le toca atacar
            $table->string('status')->default('in_progress'); // BattleStatus
            $table->unsignedInteger('turn_number')->default(0);
            $table->foreignId('winner_my_pokemon_id')->nullable()->constrained('my_pokemon')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('battles');
    }
};
