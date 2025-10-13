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
        Schema::create('rubros', function (Blueprint $table) {
            $table->id(); // BIGINT UNSIGNED, PK, NN, Autoincremental
            
            // Campos según el diccionario
            $table->string('codigo', 10)->unique(); // VARCHAR(10), NN, UQ
            $table->string('nombre', 100);          // VARCHAR(100), NN
            $table->text('descripcion')->nullable(); // TEXT
            
            // Benchmarks de ejemplo (DECIMAL(10,2))
            $table->decimal('promedio_prueba_acida', 10, 2)->nullable();
            $table->decimal('promedio_liquidez_corriente', 10, 2)->nullable();
            $table->decimal('promedio_apalancamiento', 10, 2)->nullable();
            $table->decimal('promedio_rentabilidad', 10, 2)->nullable();

            // created_at y updated_at
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rubros');
    }
};