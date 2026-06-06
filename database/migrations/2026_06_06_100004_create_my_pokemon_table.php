<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Instancia de combate: un Pokémon base "capturado" con su nivel y mote,
     * al que se le asignan hasta 4 movimientos (pivote my_pokemon_move).
     */
    public function up(): void
    {
        Schema::create('my_pokemon', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pokemon_id')->constrained('pokemon')->cascadeOnDelete();
            $table->string('nickname');
            $table->unsignedTinyInteger('level')->default(50);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('my_pokemon');
    }
};
