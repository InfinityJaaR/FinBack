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
        // Eliminamos la columna legacy `sentido` del pivot ratio_componentes.
        if (Schema::hasColumn('ratio_componentes', 'sentido')) {
            Schema::table('ratio_componentes', function (Blueprint $table) {
                $table->dropColumn('sentido');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restaurar la columna `sentido` como integer con valor por defecto 1
        Schema::table('ratio_componentes', function (Blueprint $table) {
            if (! Schema::hasColumn('ratio_componentes', 'sentido')) {
                $table->integer('sentido')->default(1)->after('orden');
            }
        });
    }
};
