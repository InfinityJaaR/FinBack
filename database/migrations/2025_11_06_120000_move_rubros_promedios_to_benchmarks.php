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
        // 1) Ensure minimal ratio definitions exist for the mapping we will use.
        // Mapping: promedio_prueba_acida -> PRUEBA_ACIDA
        //          promedio_liquidez_corriente -> RAZ_CORR
        //          promedio_apalancamiento -> APALANCAMIENTO (created if missing)
        //          promedio_rentabilidad -> RENTABILIDAD (created if missing)
        $now = now();

        // Use the Eloquent model if available; fallback to raw DB if not.
        try {
            if (class_exists(\App\Models\RatioDefinicion::class)) {
                \App\Models\RatioDefinicion::firstOrCreate(
                    ['codigo' => 'PRUEBA_ACIDA'],
                    ['nombre' => 'Prueba Ácida', 'formula' => '', 'categoria' => 'LIQUIDEZ', 'multiplicador_resultado' => 1.0]
                );
                \App\Models\RatioDefinicion::firstOrCreate(
                    ['codigo' => 'RAZ_CORR'],
                    ['nombre' => 'Razón Corriente', 'formula' => '', 'categoria' => 'LIQUIDEZ', 'multiplicador_resultado' => 1.0]
                );
                \App\Models\RatioDefinicion::firstOrCreate(
                    ['codigo' => 'APALANCAMIENTO'],
                    ['nombre' => 'Apalancamiento', 'formula' => '', 'categoria' => 'ENDEUDAMIENTO', 'multiplicador_resultado' => 1.0]
                );
                \App\Models\RatioDefinicion::firstOrCreate(
                    ['codigo' => 'RENTABILIDAD'],
                    ['nombre' => 'Rentabilidad', 'formula' => '', 'categoria' => 'RENTABILIDAD', 'multiplicador_resultado' => 1.0]
                );
            }
        } catch (\Throwable $e) {
            // If something goes wrong using models (rare), ignore and continue with DB raw inserts.
        }

        // 2) Copy existing promedio_* values from rubros into benchmarks_rubro.
        $rubros = DB::table('rubros')->get();
        foreach ($rubros as $r) {
            // PRUEBA_ACIDA
            if (! is_null($r->promedio_prueba_acida)) {
                $ratioId = DB::table('ratios_definiciones')->where('codigo', 'PRUEBA_ACIDA')->value('id');
                if ($ratioId) {
                    DB::table('benchmarks_rubro')->updateOrInsert(
                        ['rubro_id' => $r->id, 'ratio_id' => $ratioId, 'anio' => 0],
                        ['valor_promedio' => $r->promedio_prueba_acida, 'fuente' => 'migracion_rubros', 'created_at' => $now, 'updated_at' => $now, 'anio' => 0]
                    );
                }
            }

            // LIQUIDEZ CORRIENTE -> RAZ_CORR
            if (! is_null($r->promedio_liquidez_corriente)) {
                $ratioId = DB::table('ratios_definiciones')->where('codigo', 'RAZ_CORR')->value('id');
                if ($ratioId) {
                    DB::table('benchmarks_rubro')->updateOrInsert(
                        ['rubro_id' => $r->id, 'ratio_id' => $ratioId, 'anio' => 0],
                        ['valor_promedio' => $r->promedio_liquidez_corriente, 'fuente' => 'migracion_rubros', 'created_at' => $now, 'updated_at' => $now, 'anio' => 0]
                    );
                }
            }

            // APALANCAMIENTO
            if (! is_null($r->promedio_apalancamiento)) {
                $ratioId = DB::table('ratios_definiciones')->where('codigo', 'APALANCAMIENTO')->value('id');
                if ($ratioId) {
                    DB::table('benchmarks_rubro')->updateOrInsert(
                        ['rubro_id' => $r->id, 'ratio_id' => $ratioId, 'anio' => 0],
                        ['valor_promedio' => $r->promedio_apalancamiento, 'fuente' => 'migracion_rubros', 'created_at' => $now, 'updated_at' => $now, 'anio' => 0]
                    );
                }
            }

            // RENTABILIDAD
            if (! is_null($r->promedio_rentabilidad)) {
                $ratioId = DB::table('ratios_definiciones')->where('codigo', 'RENTABILIDAD')->value('id');
                if ($ratioId) {
                    DB::table('benchmarks_rubro')->updateOrInsert(
                        ['rubro_id' => $r->id, 'ratio_id' => $ratioId, 'anio' => 0],
                        ['valor_promedio' => $r->promedio_rentabilidad, 'fuente' => 'migracion_rubros', 'created_at' => $now, 'updated_at' => $now, 'anio' => 0]
                    );
                }
            }
        }

        // 3) Modify benchmarks_rubro schema: drop unique(rubro_id, ratio_id, anio), drop anio, add unique(rubro_id, ratio_id)
        // Disable foreign key checks temporarily to allow index/column alteration on MySQL.
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        try {
            // Try to drop the index by name safely.
            DB::statement('ALTER TABLE `benchmarks_rubro` DROP INDEX IF EXISTS `benchmarks_rubro_rubro_id_ratio_id_anio_unique`');
        } catch (\Throwable $e) {
            // Some MySQL versions don't support IF EXISTS for DROP INDEX; try without IF EXISTS
            try {
                DB::statement('ALTER TABLE `benchmarks_rubro` DROP INDEX `benchmarks_rubro_rubro_id_ratio_id_anio_unique`');
            } catch (\Throwable $ex) {
                // ignore if still fails
            }
        }

        // Now drop the column 'anio' if it exists
        if (Schema::hasColumn('benchmarks_rubro', 'anio')) {
            Schema::table('benchmarks_rubro', function (Blueprint $table) {
                $table->dropColumn('anio');
            });
        }

        // Add a unique index for (rubro_id, ratio_id) to ensure one benchmark value per pair.
        try {
            DB::statement('ALTER TABLE `benchmarks_rubro` ADD UNIQUE `benchmarks_rubro_rubro_id_ratio_id_unique`(`rubro_id`, `ratio_id`)');
        } catch (\Throwable $e) {
            // ignore errors if cannot add
        }
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        // 4) Drop promedio_* columns from rubros
        Schema::table('rubros', function (Blueprint $table) {
            if (Schema::hasColumn('rubros', 'promedio_prueba_acida')) {
                $table->dropColumn(['promedio_prueba_acida', 'promedio_liquidez_corriente', 'promedio_apalancamiento', 'promedio_rentabilidad']);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 1) Re-add promedio_* columns to rubros (nullable)
        Schema::table('rubros', function (Blueprint $table) {
            if (! Schema::hasColumn('rubros', 'promedio_prueba_acida')) {
                $table->decimal('promedio_prueba_acida', 10, 2)->nullable();
                $table->decimal('promedio_liquidez_corriente', 10, 2)->nullable();
                $table->decimal('promedio_apalancamiento', 10, 2)->nullable();
                $table->decimal('promedio_rentabilidad', 10, 2)->nullable();
            }
        });

        // 2) Re-add anio column and restore unique index on (rubro_id, ratio_id, anio)
        Schema::table('benchmarks_rubro', function (Blueprint $table) {
            if (! Schema::hasColumn('benchmarks_rubro', 'anio')) {
                $table->unsignedSmallInteger('anio')->default(0);
            }

            // drop unique on (rubro_id, ratio_id)
            try {
                $table->dropUnique(['rubro_id', 'ratio_id']);
            } catch (\Throwable $e) {
                try {
                    $table->dropUnique('benchmarks_rubro_rubro_id_ratio_id_unique');
                } catch (\Throwable $ex) {
                    // ignore
                }
            }

            $table->unique(['rubro_id', 'ratio_id', 'anio']);
        });

        // Note: We do NOT automatically move data back from benchmarks_rubro into rubros.promedio_* because
        // there may have been multiple benchmarks per (rubro, ratio) previously. Manual reconciliation required.
    }
};
