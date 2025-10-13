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
        Schema::create('ventas_mensuales', function (Blueprint $table) {
            $table->id(); // BIGINT UNSIGNED, PK, NN, Autoincremental
            
            // Llave Foránea a Empresas
            $table->foreignId('empresa_id')->constrained('empresas')->onDelete('cascade'); // NN, FK
            
            // Campos según el diccionario
            $table->date('fecha'); // DATE, NN (Usar el día 01 del mes para la serie temporal)
            $table->decimal('monto', 18, 2); // DECIMAL(18,2), NN
            
            // Restricción: No puede haber dos registros de ventas para la misma empresa en la misma fecha (mes/año)
            $table->unique(['empresa_id', 'fecha']);

            // created_at y updated_at
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ventas_mensuales');
    }
};