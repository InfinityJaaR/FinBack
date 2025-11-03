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
        Schema::table('catalogo_cuentas', function (Blueprint $table) {
            $table->enum('estado_financiero', ['BALANCE_GENERAL', 'ESTADO_RESULTADOS', 'NINGUNO'])
                  ->default('NINGUNO')
                  ->after('es_calculada');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('catalogo_cuentas', function (Blueprint $table) {
            $table->dropColumn('estado_financiero');
        });
    }
};
