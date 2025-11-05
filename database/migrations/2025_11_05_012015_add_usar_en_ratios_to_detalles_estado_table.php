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
        Schema::table('detalles_estado', function (Blueprint $table) {
            $table->boolean('usar_en_ratios')->default(false)->after('monto')
                ->comment('Indica si esta cuenta debe ser considerada en el cálculo de ratios financieros');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('detalles_estado', function (Blueprint $table) {
            $table->dropColumn('usar_en_ratios');
        });
    }
};
