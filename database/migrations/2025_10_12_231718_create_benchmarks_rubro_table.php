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
        Schema::create('benchmarks_rubro', function (Blueprint $table) {
            $table->id(); // BIGINT UNSIGNED, PK, NN, Autoincremental
            
            // Llaves Foráneas
            $table->foreignId('rubro_id')->constrained('rubros')->onDelete('cascade'); // NN, FK
            $table->foreignId('ratio_id')->constrained('ratios_definiciones')->onDelete('cascade'); // NN, FK
            
            // Campos según el diccionario
            $table->unsignedSmallInteger('anio'); // INT (Año de referencia)
            $table->decimal('valor_promedio', 18, 6); // DECIMAL(18,6), NN
            $table->string('fuente', 150)->nullable(); // VARCHAR(150) (Libro, ministerio, etc.) [cite: 48]
            
            // Restricción: No puede haber el mismo benchmark para el mismo rubro, ratio y año.
            $table->unique(['rubro_id', 'ratio_id', 'anio']);

            // created_at y updated_at
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('benchmarks_rubro');
    }
};