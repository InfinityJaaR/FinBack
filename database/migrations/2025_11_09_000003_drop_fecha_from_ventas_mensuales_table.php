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
        // Debemos eliminar los índices únicos que referencian 'fecha' antes de dropear la columna
        Schema::table('ventas_mensuales', function (Blueprint $table) {
            // intentar borrar ambos posibles nombres de índice único
            try { $table->dropUnique('ventas_mensuales_unique_empresa_fecha'); } catch (\Throwable $e) {}
            try { $table->dropUnique(['empresa_id', 'fecha']); } catch (\Throwable $e) {}
        });

        Schema::table('ventas_mensuales', function (Blueprint $table) {
            if (Schema::hasColumn('ventas_mensuales', 'fecha')) {
                $table->dropColumn('fecha');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ventas_mensuales', function (Blueprint $table) {
            if (! Schema::hasColumn('ventas_mensuales', 'fecha')) {
                $table->date('fecha')->nullable()->after('empresa_id');
            }
        });
    }
};
