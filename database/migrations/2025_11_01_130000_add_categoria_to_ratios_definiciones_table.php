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
        Schema::table('ratios_definiciones', function (Blueprint $table) {
            // categoria: agrupa el ratio en Liq/Endeudamiento/Rentabilidad/Eficiencia/Cobertura
            $table->string('categoria')->default('SIN_CATEGORIA')->after('sentido');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ratios_definiciones', function (Blueprint $table) {
            $table->dropColumn('categoria');
        });
    }
};
