<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ventas_mensuales', function (Blueprint $table) {
            // Ensure index name is unique and explicit to avoid naming collisions
            $table->unique(['empresa_id', 'fecha'], 'ventas_mensuales_unique_empresa_fecha');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('ventas_mensuales', function (Blueprint $table) {
            $table->dropUnique('ventas_mensuales_unique_empresa_fecha');
        });
    }
};
