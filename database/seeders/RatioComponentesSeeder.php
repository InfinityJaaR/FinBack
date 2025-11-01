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
            RatioComponente::firstOrCreate([
                'ratio_id'=>$ratio->id,'concepto_id'=>1,'rol'=>'NUMERADOR','orden'=>1 // ACTIVO_CORRIENTE
            ], ['sentido'=>1]);
            RatioComponente::firstOrCreate([
                'ratio_id'=>$ratio->id,'concepto_id'=>2,'rol'=>'DENOMINADOR','orden'=>1 // PASIVO_CORRIENTE
            ], ['sentido'=>1]);
        }

        // PRUEBA ÁCIDA ((AC - INV) / PC)
        $ratio = RatioDefinicion::where('codigo','PRB_ACIDA')->first();
        if ($ratio) {
            RatioComponente::firstOrCreate([
                'ratio_id'=>$ratio->id,'concepto_id'=>1,'rol'=>'NUMERADOR','orden'=>1 // ACTIVO_CORRIENTE
            ], ['sentido'=>1]);
            RatioComponente::firstOrCreate([
                'ratio_id'=>$ratio->id,'concepto_id'=>3,'rol'=>'NUMERADOR','orden'=>2 // INVENTARIO
            ], ['sentido'=>-1]); // se resta
            RatioComponente::firstOrCreate([
                'ratio_id'=>$ratio->id,'concepto_id'=>2,'rol'=>'DENOMINADOR','orden'=>3 // PASIVO_CORRIENTE
            ], ['sentido'=>1]);
        }

        // MARGEN OPERACIONAL (UT / VENTAS)
        $ratio = RatioDefinicion::where('codigo','MARG_OP')->first();
        if ($ratio) {
            RatioComponente::firstOrCreate([
                'ratio_id'=>$ratio->id,'concepto_id'=>7,'rol'=>'NUMERADOR','orden'=>1 // UTILIDAD_NETA
            ], ['sentido'=>1]);
            RatioComponente::firstOrCreate([
                'ratio_id'=>$ratio->id,'concepto_id'=>5,'rol'=>'DENOMINADOR','orden'=>2 // VENTAS_NETAS
            ], ['sentido'=>1]);
        }

        // ROE (UT / PATRIMONIO)
        $ratio = RatioDefinicion::where('codigo','ROE')->first();
        if ($ratio) {
            RatioComponente::firstOrCreate([
                'ratio_id'=>$ratio->id,'concepto_id'=>7,'rol'=>'NUMERADOR','orden'=>1 // UTILIDAD_NETA
            ], ['sentido'=>1]);
            RatioComponente::firstOrCreate([
                'ratio_id'=>$ratio->id,'concepto_id'=>8,'rol'=>'DENOMINADOR','orden'=>2 // TOTAL_PATRIMONIO
            ], ['sentido'=>1]);
        }
    }
}
