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
        Schema::create('ratios_valores', function (Blueprint $table) {
            $table->id(); // BIGINT UNSIGNED, PK, NN, Autoincremental
            
            // Llaves Foráneas
            $table->foreignId('empresa_id')->constrained('empresas')->onDelete('cascade'); // NN, FK
            $table->foreignId('periodo_id')->constrained('periodos')->onDelete('cascade'); // NN, FK
            $table->foreignId('ratio_id')->constrained('ratios_definiciones')->onDelete('cascade'); // NN, FK
            
            // Campos según el diccionario
            $table->decimal('valor', 18, 6); // DECIMAL(18,6), NN
            $table->enum('fuente', ['CALCULADO', 'MANUAL']); // ENUM, NN (Origen del valor)
            
            // Restricción sugerida: No puede haber el mismo ratio para la misma empresa y periodo.
            $table->unique(['empresa_id', 'periodo_id', 'ratio_id']);
            
            // created_at y updated_at
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ratios_valores');
    }
};