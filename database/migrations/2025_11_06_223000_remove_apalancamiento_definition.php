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
        // Remove the APALANCAMIENTO ratio definition and any related components/benchmarks.
        $ratio = DB::table('ratios_definiciones')->where('codigo', 'APALANCAMIENTO')->first();
        if ($ratio) {
            $ratioId = $ratio->id;

            // delete componentes referencing the ratio (pivot uses ratio_id)
            DB::table('ratio_componentes')->where('ratio_id', $ratioId)->delete();

            // delete benchmarks for that ratio
            DB::table('benchmarks_rubro')->where('ratio_id', $ratioId)->delete();

            // finally delete the ratio definition
            DB::table('ratios_definiciones')->where('id', $ratioId)->delete();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Recreate a minimal APALANCAMIENTO definition (no componentes) to reverse the removal.
        DB::table('ratios_definiciones')->updateOrInsert(
            ['codigo' => 'APALANCAMIENTO'],
            ['nombre' => 'Apalancamiento', 'formula' => '', 'sentido' => 'MAYOR_MEJOR', 'categoria' => 'ENDEUDAMIENTO', 'multiplicador_resultado' => 1.0, 'is_protected' => false, 'created_at' => now(), 'updated_at' => now()]
        );
    }
};
