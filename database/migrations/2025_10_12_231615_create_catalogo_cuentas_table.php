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
        Schema::create('catalogo_cuentas', function (Blueprint $table) {
            $table->id(); // BIGINT UNSIGNED, PK, NN, Autoincremental
            
            // Llave Foránea a Empresas
            // Si la empresa se elimina, sus catálogos también deben eliminarse.
            $table->foreignId('empresa_id')->constrained('empresas')->onDelete('cascade'); // BIGINT UNSIGNED, NN, FK
            
            // Campos según el diccionario
            $table->string('codigo', 50); // VARCHAR(50), NN. (No es UQ global, solo UQ dentro de la empresa)
            $table->string('nombre', 150); // VARCHAR(150), NN
            
            // Clasificación macro para los EF
            $table->enum('tipo', ['ACTIVO', 'PASIVO', 'PATRIMONIO', 'INGRESO', 'GASTO']); // ENUM, NN
            
            $table->boolean('es_calculada')->default(0); // TINYINT(1): 0=valor directo (ingresado), 1=derivada
            
            // Restricción: No puede haber el mismo código de cuenta para una misma empresa
            $table->unique(['empresa_id', 'codigo']);
            
            // created_at y updated_at
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('catalogo_cuentas');
    }
};