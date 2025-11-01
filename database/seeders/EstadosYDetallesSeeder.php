<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EstadosYDetallesSeeder extends Seeder
{
    const EMPRESA_ID = 1;

    public function run(): void
    {
        $periodoId = DB::table('periodos')->where('anio', 2024)->value('id');

        // Crear los estados (uno de balance y uno de resultados)
        DB::table('estados')->updateOrInsert(
            ['empresa_id'=>self::EMPRESA_ID,'periodo_id'=>$periodoId,'tipo'=>'BALANCE'],
            ['created_at'=>now(),'updated_at'=>now()]
        );

        DB::table('estados')->updateOrInsert(
            ['empresa_id'=>self::EMPRESA_ID,'periodo_id'=>$periodoId,'tipo'=>'RESULTADOS'],
            ['created_at'=>now(),'updated_at'=>now()]
        );

        $balanceId = DB::table('estados')->where([
            'empresa_id'=>self::EMPRESA_ID,
            'periodo_id'=>$periodoId,
            'tipo'=>'BALANCE'
        ])->value('id');

        $resultadosId = DB::table('estados')->where([
            'empresa_id'=>self::EMPRESA_ID,
            'periodo_id'=>$periodoId,
            'tipo'=>'RESULTADOS'
        ])->value('id');

        // Limpia los detalles anteriores
        DB::table('detalles_estado')->whereIn('estado_id', [$balanceId, $resultadosId])->delete();

        // Inserta montos ejemplo
        DB::table('detalles_estado')->insert([
            // BALANCE
            ['estado_id'=>$balanceId,   'catalogo_cuenta_id'=>101,'monto'=>15000.00,'created_at'=>now(),'updated_at'=>now()],
            ['estado_id'=>$balanceId,   'catalogo_cuenta_id'=>102,'monto'=> 9000.00,'created_at'=>now(),'updated_at'=>now()],
            ['estado_id'=>$balanceId,   'catalogo_cuenta_id'=>103,'monto'=> 3000.00,'created_at'=>now(),'updated_at'=>now()],
            ['estado_id'=>$balanceId,   'catalogo_cuenta_id'=>106,'monto'=>16000.00,'created_at'=>now(),'updated_at'=>now()],

            // RESULTADOS
            ['estado_id'=>$resultadosId,'catalogo_cuenta_id'=>104,'monto'=>30000.00,'created_at'=>now(),'updated_at'=>now()],
            ['estado_id'=>$resultadosId,'catalogo_cuenta_id'=>105,'monto'=> 4800.00,'created_at'=>now(),'updated_at'=>now()],
        ]);
    }
}
