<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\RatioDefinicion;
use App\Models\RatioComponente;

class RatioComponentesSeeder extends Seeder
{
    public function run(): void
    {
        // LIQUIDEZ CORRIENTE (AC / PC)
        $ratio = RatioDefinicion::where('codigo','LIQ_CORR')->first();
        if ($ratio) {
            // Crear usando ratio_id + concepto_id como clave de búsqueda para evitar intentos de inserir duplicados
            RatioComponente::firstOrCreate(
                ['ratio_id' => $ratio->id, 'concepto_id' => 1],
                ['rol' => 'NUMERADOR', 'orden' => 1, 'sentido' => 1]
            ); // ACTIVO_CORRIENTE
            RatioComponente::firstOrCreate(
                ['ratio_id' => $ratio->id, 'concepto_id' => 2],
                ['rol' => 'DENOMINADOR', 'orden' => 1, 'sentido' => 1]
            ); // PASIVO_CORRIENTE
        }

        // PRUEBA ÁCIDA ((AC - INV) / PC)
        $ratio = RatioDefinicion::where('codigo','PRB_ACIDA')->first();
        if ($ratio) {
            RatioComponente::firstOrCreate(
                ['ratio_id' => $ratio->id, 'concepto_id' => 1],
                ['rol' => 'NUMERADOR', 'orden' => 1, 'sentido' => 1]
            ); // ACTIVO_CORRIENTE
            RatioComponente::firstOrCreate(
                ['ratio_id' => $ratio->id, 'concepto_id' => 3],
                ['rol' => 'NUMERADOR', 'orden' => 2, 'sentido' => -1]
            ); // INVENTARIO (se resta)
            RatioComponente::firstOrCreate(
                ['ratio_id' => $ratio->id, 'concepto_id' => 2],
                ['rol' => 'DENOMINADOR', 'orden' => 3, 'sentido' => 1]
            ); // PASIVO_CORRIENTE
        }

        // MARGEN OPERACIONAL (UT / VENTAS)
        $ratio = RatioDefinicion::where('codigo','MARG_OP')->first();
        if ($ratio) {
            RatioComponente::firstOrCreate(
                ['ratio_id' => $ratio->id, 'concepto_id' => 7],
                ['rol' => 'NUMERADOR', 'orden' => 1, 'sentido' => 1]
            ); // UTILIDAD_NETA
            RatioComponente::firstOrCreate(
                ['ratio_id' => $ratio->id, 'concepto_id' => 5],
                ['rol' => 'DENOMINADOR', 'orden' => 2, 'sentido' => 1]
            ); // VENTAS_NETAS
        }

        // ROE (UT / PATRIMONIO)
        $ratio = RatioDefinicion::where('codigo','ROE')->first();
        if ($ratio) {
            RatioComponente::firstOrCreate(
                ['ratio_id' => $ratio->id, 'concepto_id' => 7],
                ['rol' => 'NUMERADOR', 'orden' => 1, 'sentido' => 1]
            ); // UTILIDAD_NETA
            RatioComponente::firstOrCreate(
                ['ratio_id' => $ratio->id, 'concepto_id' => 8],
                ['rol' => 'DENOMINADOR', 'orden' => 2, 'sentido' => 1]
            ); // TOTAL_PATRIMONIO
        }
    }
}
