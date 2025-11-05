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
        Schema::table('conceptos_financieros', function (Blueprint $table) {
            // Añadimos `codigo` como campo identificador usado por seeders y lógica de negocio.
            // Lo dejamos nullable inicialmente para evitar problemas en entornos con datos
            // existentes; puede hacerse not null en una migración posterior tras audit.
            $table->string('codigo', 100)->nullable()->after('nombre_concepto');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('conceptos_financieros', function (Blueprint $table) {
            $table->dropColumn('codigo');
        });
    }
};
