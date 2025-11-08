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
        Schema::create('proyecciones_detalle', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proyeccion_id')->constrained('proyecciones')->onDelete('cascade');
            $table->date('fecha_proyectada');
            $table->decimal('monto_proyectado', 15, 2);
            $table->timestamps();

            $table->index('proyeccion_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('proyecciones_detalle');
    }
};
