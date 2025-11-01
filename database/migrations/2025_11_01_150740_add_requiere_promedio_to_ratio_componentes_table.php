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
        // Añade la columna booleana 'requiere_promedio' a la tabla pivote
        Schema::table('ratio_componentes', function (Blueprint $table) {
            $table->boolean('requiere_promedio')
                  ->default(false)
                  ->after('orden')
                  ->comment('Indica si el cálculo de este componente debe usar el promedio del periodo actual y el anterior.');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Elimina la columna si se revierte la migración
        Schema::table('ratio_componentes', function (Blueprint $table) {
            $table->dropColumn('requiere_promedio');
        });
    }
};
