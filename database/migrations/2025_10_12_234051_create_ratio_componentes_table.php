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
        Schema::create('ratio_componentes', function (Blueprint $table) {
            // No usamos id() porque es una tabla pivote simple (clave compuesta)
            
            // Llave Foránea a la Definición del Ratio
            $table->foreignId('ratio_id')
                  ->constrained('ratios_definiciones')
                  ->onDelete('cascade'); // Si se elimina el ratio, se eliminan sus componentes.

            // Llave Foránea al Concepto Financiero (ej. Activo Corriente)
            // Asumiendo que has renombrado CONCEPTOFINANCIERO a 'conceptos_financieros'
            $table->foreignId('concepto_id') 
                  ->constrained('conceptos_financieros') 
                  ->onDelete('restrict');

            // Define el rol del concepto en la fórmula (clave para el cálculo)
            // Ej: ACTIVO_CORRIENTE es NUMERADOR, PASIVO_CORRIENTE es DENOMINADOR.
            $table->enum('rol', ['NUMERADOR', 'DENOMINADOR', 'OPERANDO']); 
            
            // Si el cálculo es complejo (A + B - C / D), este orden es crucial
            $table->unsignedSmallInteger('orden'); 

            // Define la clave primaria compuesta
            $table->primary(['ratio_id', 'concepto_id']);

            // Timestamps opcionales, pero recomendados para Laravel
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ratio_componentes');
    }
};