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
        Schema::create('periodos', function (Blueprint $table) {
            $table->id(); // BIGINT UNSIGNED, PK, NN, Autoincremental
            
            // Campos según el diccionario
            $table->integer('anio')->unique();      // INT, NN. Lo hacemos único ya que no se espera repetir el año.
            $table->date('fecha_inicio');           // DATE, NN
            $table->date('fecha_fin');              // DATE, NN
            
            // created_at y updated_at
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('periodos');
    }
};