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
        Schema::create('empresas', function (Blueprint $table) {
            $table->id(); // BIGINT UNSIGNED, PK, NN, Autoincremental
            
            // Llave Foránea a Rubros
            $table->foreignId('rubro_id')->constrained('rubros')->onDelete('restrict'); // BIGINT UNSIGNED, NN, FK
            
            // Campos según el diccionario
            $table->string('codigo', 20)->unique();  // VARCHAR(20), NN, UQ
            $table->string('nombre', 150);           // VARCHAR(150), NN (Razón social)
            $table->text('descripcion')->nullable(); // TEXT
            
            // created_at y updated_at
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('empresas');
    }
};