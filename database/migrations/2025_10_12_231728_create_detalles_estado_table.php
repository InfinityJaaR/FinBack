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
        Schema::create('detalles_estado', function (Blueprint $table) {
            $table->id(); // BIGINT UNSIGNED, PK, NN, Autoincremental
            
            // Llaves Foráneas
            $table->foreignId('estado_id')->constrained('estados')->onDelete('cascade'); // NN, FK
            // Se asocia a la cuenta específica en el catálogo de esa empresa
            $table->foreignId('catalogo_cuenta_id')->constrained('catalogo_cuentas')->onDelete('restrict'); // NN, FK
            
            // Campo de monto
            $table->decimal('monto', 18, 2); // DECIMAL(18,2), NN

            // Restricción: Una cuenta solo puede aparecer una vez en un detalle de estado.
            $table->unique(['estado_id', 'catalogo_cuenta_id']);
            
            // created_at y updated_at
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detalles_estado');
    }
};