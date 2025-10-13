<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ratios_definiciones', function (Blueprint $table) {
            $table->id(); // BIGINT UNSIGNED, PK, NN, Autoincremental
            
            // Campos según el diccionario
            $table->string('codigo', 30)->unique(); // VARCHAR(30), NN, UQ
            $table->string('nombre', 120);          // VARCHAR(120), NN
            $table->text('formula');                // TEXT, NN (sirve como documentación)
            
            // Sentido de interpretación
            $table->enum('sentido', ['MAYOR_MEJOR', 'MENOR_MEJOR', 'CERCANO_A_1']); // ENUM, NN

            // created_at y updated_at
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ratios_definiciones');
    }
};