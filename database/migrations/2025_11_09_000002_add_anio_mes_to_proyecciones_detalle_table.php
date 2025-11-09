<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('proyecciones_detalle', function (Blueprint $table) {
            $table->smallInteger('anio')->nullable()->after('fecha_proyectada');
            $table->tinyInteger('mes')->nullable()->after('anio');
        });

        DB::statement('UPDATE proyecciones_detalle SET anio = YEAR(fecha_proyectada), mes = MONTH(fecha_proyectada)');

        Schema::table('proyecciones_detalle', function (Blueprint $table) {
            // Un detalle por proyección y mes: garantiza integridad.
            $table->unique(['proyeccion_id', 'anio', 'mes'], 'proyecciones_detalle_proyeccion_anio_mes_unique');
            $table->index(['anio', 'mes'], 'proyecciones_detalle_anio_mes_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('proyecciones_detalle', function (Blueprint $table) {
            $table->dropUnique('proyecciones_detalle_proyeccion_anio_mes_unique');
            $table->dropIndex('proyecciones_detalle_anio_mes_index');
            $table->dropColumn('anio');
            $table->dropColumn('mes');
        });
    }
};
