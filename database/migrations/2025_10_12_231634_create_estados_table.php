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
        Schema::create('estados', function (Blueprint $table) {
            $table->id(); // BIGINT UNSIGNED, PK, NN, Autoincremental
            
            // Llaves Foráneas
            $table->foreignId('empresa_id')->constrained('empresas')->onDelete('cascade'); // NN, FK
            $table->foreignId('periodo_id')->constrained('periodos')->onDelete('cascade'); // NN, FK
            
            // Tipo de estado financiero
            $table->enum('tipo', ['BALANCE', 'RESULTADOS']); // ENUM, NN
            
            // Restricción: Una empresa solo puede tener un tipo de estado (BALANCE o RESULTADOS) por periodo
            $table->unique(['empresa_id', 'periodo_id', 'tipo']);

            // created_at y updated_at
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('estados');
    }
};