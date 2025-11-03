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
        Schema::table('users', function (Blueprint $table) {
            // Agregar empresa_id nullable con foreign key a empresas
            $table->foreignId('empresa_id')
                ->nullable()
                ->after('active')
                ->constrained('empresas')
                ->onDelete('set null'); // Si se elimina la empresa, el usuario queda sin empresa asignada
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Eliminar la foreign key y la columna
            $table->dropForeign(['empresa_id']);
            $table->dropColumn('empresa_id');
        });
    }
};
