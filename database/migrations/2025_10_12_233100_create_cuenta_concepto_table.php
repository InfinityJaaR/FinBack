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
        Schema::create('cuenta_concepto', function (Blueprint $table) {
            // Tabla pivote sin ID autoincremental, usando clave compuesta

            // Llave Foránea a la cuenta específica de una empresa (catalogo_cuentas)
            $table->foreignId('catalogo_cuenta_id')
                  ->constrained('catalogo_cuentas')
                  ->onDelete('cascade'); // Si la cuenta se elimina, se elimina el mapeo.

            // Llave Foránea al Concepto Financiero abstracto (ej. Activo Corriente)
            $table->foreignId('concepto_id') 
                  ->constrained('conceptos_financieros') 
                  ->onDelete('restrict');

            // Define la clave primaria compuesta
            $table->primary(['catalogo_cuenta_id', 'concepto_id']);

            // Timestamps opcionales
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cuenta_concepto');
    }
};