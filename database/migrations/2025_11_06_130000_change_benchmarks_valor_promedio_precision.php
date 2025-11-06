<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Cambiamos la precisión a 2 decimales. Usamos statement para evitar dependencia de doctrine/dbal.
        DB::statement('ALTER TABLE `benchmarks_rubro` MODIFY `valor_promedio` DECIMAL(18,2) NOT NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE `benchmarks_rubro` MODIFY `valor_promedio` DECIMAL(18,6) NOT NULL');
    }
};
