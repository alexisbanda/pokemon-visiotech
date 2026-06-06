<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pivote N:N: los movimientos *elegidos* de un MyPokemon (máx. 4, validado
     * en el Form Request).
     */
    public function up(): void
    {
        Schema::create('my_pokemon_move', function (Blueprint $table) {
            $table->foreignId('my_pokemon_id')->constrained('my_pokemon')->cascadeOnDelete();
            $table->foreignId('move_id')->constrained('moves')->cascadeOnDelete();
            $table->primary(['my_pokemon_id', 'move_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('my_pokemon_move');
    }
};
