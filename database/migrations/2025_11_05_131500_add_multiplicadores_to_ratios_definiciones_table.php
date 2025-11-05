<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('ratios_definiciones')) {
            Schema::table('ratios_definiciones', function (Blueprint $table) {
                if (! Schema::hasColumn('ratios_definiciones', 'multiplicador_numerador')) {
                    $table->decimal('multiplicador_numerador', 20, 6)->nullable()->after('categoria');
                }
                if (! Schema::hasColumn('ratios_definiciones', 'multiplicador_denominador')) {
                    $table->decimal('multiplicador_denominador', 20, 6)->nullable()->after('multiplicador_numerador');
                }
                if (! Schema::hasColumn('ratios_definiciones', 'multiplicador_resultado')) {
                    $table->decimal('multiplicador_resultado', 20, 6)->nullable()->after('multiplicador_denominador');
                }
            });

            // Si existe la columna antigua 'multiplicador', copiar su valor a multiplicador_resultado
            try {
                if (Schema::hasColumn('ratios_definiciones', 'multiplicador')) {
                    DB::table('ratios_definiciones')
                        ->whereNotNull('multiplicador')
                        ->update(['multiplicador_resultado' => DB::raw('multiplicador')]);
                }
            } catch (\Throwable $e) {
                logger()->warning('Error copiando multiplicador -> multiplicador_resultado: ' . $e->getMessage());
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasTable('ratios_definiciones')) {
            // Restaurar el antiguo campo multiplicador si existe y es necesario
            try {
                if (Schema::hasColumn('ratios_definiciones', 'multiplicador_resultado') && Schema::hasColumn('ratios_definiciones', 'multiplicador')) {
                    DB::table('ratios_definiciones')
                        ->whereNotNull('multiplicador_resultado')
                        ->update(['multiplicador' => DB::raw('multiplicador_resultado')]);
                }
            } catch (\Throwable $e) {
                logger()->warning('Error restaurando multiplicador desde multiplicador_resultado: ' . $e->getMessage());
            }

            Schema::table('ratios_definiciones', function (Blueprint $table) {
                if (Schema::hasColumn('ratios_definiciones', 'multiplicador_resultado')) {
                    $table->dropColumn('multiplicador_resultado');
                }
                if (Schema::hasColumn('ratios_definiciones', 'multiplicador_denominador')) {
                    $table->dropColumn('multiplicador_denominador');
                }
                if (Schema::hasColumn('ratios_definiciones', 'multiplicador_numerador')) {
                    $table->dropColumn('multiplicador_numerador');
                }
            });
        }
    }
};
