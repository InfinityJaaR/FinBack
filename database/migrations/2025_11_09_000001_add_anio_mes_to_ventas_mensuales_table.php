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
        // 1. Añadir columnas anio, mes (inicialmente NULL para poder backfill sin requerir doctrine/dbal)
        Schema::table('ventas_mensuales', function (Blueprint $table) {
            $table->smallInteger('anio')->nullable()->after('fecha');
            $table->tinyInteger('mes')->nullable()->after('anio');
        });

        // 2. Backfill desde columna fecha (YYYY-MM-DD)
        DB::statement('UPDATE ventas_mensuales SET anio = YEAR(fecha), mes = MONTH(fecha)');

        // 3. Opcional: asegurarnos de que no existen nulos (si hubiera registros sin fecha se quedarán nulos)
        //    Si se espera que todos tengan fecha, podemos continuar y crear índice único.

        // 4. Crear índice único por empresa/periodo (mantiene compatibilidad con índice anterior por fecha)
        Schema::table('ventas_mensuales', function (Blueprint $table) {
            $table->unique(['empresa_id', 'anio', 'mes'], 'ventas_mensuales_empresa_anio_mes_unique');
            $table->index(['anio', 'mes'], 'ventas_mensuales_anio_mes_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ventas_mensuales', function (Blueprint $table) {
            $table->dropUnique('ventas_mensuales_empresa_anio_mes_unique');
            $table->dropIndex('ventas_mensuales_anio_mes_index');
            $table->dropColumn('anio');
            $table->dropColumn('mes');
        });
    }
};
