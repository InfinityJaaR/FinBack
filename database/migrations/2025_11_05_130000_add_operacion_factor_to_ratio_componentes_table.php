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
        // Agregar columnas operacion y factor al pivot ratio_componentes
        if (Schema::hasTable('ratio_componentes')) {
            Schema::table('ratio_componentes', function (Blueprint $table) {
                if (! Schema::hasColumn('ratio_componentes', 'operacion')) {
                    $table->string('operacion')->nullable()->after('orden');
                }

                if (! Schema::hasColumn('ratio_componentes', 'factor')) {
                    // precision alta por si queremos factores como 365 o porcentajes 0.01
                    $table->decimal('factor', 20, 6)->nullable()->after('operacion');
                }
            });

            // Migrar valores existentes de `sentido` a `operacion` cuando aplique
            try {
                // sentido = 1  -> ADD
                DB::table('ratio_componentes')->where('sentido', 1)->update(['operacion' => 'ADD']);
                // sentido = -1 -> SUB
                DB::table('ratio_componentes')->where('sentido', -1)->update(['operacion' => 'SUB']);
            } catch (\Throwable $e) {
                // En migraciones no detenemos la ejecución por seguridad, pero dejamos registro en el log
                logger()->warning('Error migrando sentido->operacion en ratio_componentes: ' . $e->getMessage());
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
        if (Schema::hasTable('ratio_componentes')) {
            // Restaurar parcialmente sentido desde operacion cuando sea posible
            try {
                if (Schema::hasColumn('ratio_componentes', 'operacion') && Schema::hasColumn('ratio_componentes', 'sentido')) {
                    DB::table('ratio_componentes')->where('operacion', 'ADD')->update(['sentido' => 1]);
                    DB::table('ratio_componentes')->where('operacion', 'SUB')->update(['sentido' => -1]);
                }
            } catch (\Throwable $e) {
                logger()->warning('Error restaurando sentido desde operacion en ratio_componentes: ' . $e->getMessage());
            }

            Schema::table('ratio_componentes', function (Blueprint $table) {
                if (Schema::hasColumn('ratio_componentes', 'factor')) {
                    $table->dropColumn('factor');
                }
                if (Schema::hasColumn('ratio_componentes', 'operacion')) {
                    $table->dropColumn('operacion');
                }
            });
        }
    }
};
