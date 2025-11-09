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
        Schema::table('proyecciones_detalle', function (Blueprint $table) {
            if (Schema::hasColumn('proyecciones_detalle', 'fecha_proyectada')) {
                $table->dropColumn('fecha_proyectada');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('proyecciones_detalle', function (Blueprint $table) {
            if (! Schema::hasColumn('proyecciones_detalle', 'fecha_proyectada')) {
                $table->date('fecha_proyectada')->nullable()->after('proyeccion_id');
            }
        });
    }
};
