<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CatalogoYMapeoSeeder extends Seeder
{
    const EMPRESA_ID = 1;

    public function run(): void
    {
        // Cuentas base
        $cuentas = [
            ['id'=>101,'codigo'=>'AC_CTE','nombre'=>'Activo Corriente','tipo'=>'ACTIVO','es_calculada'=>0],
            ['id'=>102,'codigo'=>'PS_CTE','nombre'=>'Pasivo Corriente','tipo'=>'PASIVO','es_calculada'=>0],
            ['id'=>103,'codigo'=>'INV','nombre'=>'Inventario','tipo'=>'ACTIVO','es_calculada'=>0],
            ['id'=>104,'codigo'=>'VENT_NET','nombre'=>'Ventas Netas','tipo'=>'INGRESO','es_calculada'=>0],
            ['id'=>105,'codigo'=>'UT_NETA','nombre'=>'Utilidad Neta','tipo'=>'INGRESO','es_calculada'=>0],
            ['id'=>106,'codigo'=>'PAT_TOT','nombre'=>'Total Patrimonio','tipo'=>'PATRIMONIO','es_calculada'=>0],
        ];

        foreach ($cuentas as $c) {
            DB::table('catalogo_cuentas')->updateOrInsert(
                ['id' => $c['id']],
                [
                    'empresa_id'   => self::EMPRESA_ID,
                    'codigo'       => $c['codigo'],
                    'nombre'       => $c['nombre'],
                    'tipo'         => $c['tipo'],
                    'es_calculada' => $c['es_calculada'],
                    'updated_at'   => now(),
                    'created_at'   => now(),
                ]
            );
        }

        // Mapeo cuenta → concepto (usando tus IDs reales)
        $map = [
            ['catalogo_cuenta_id'=>101,'concepto_id'=>1], // ACTIVO_CORRIENTE
            ['catalogo_cuenta_id'=>102,'concepto_id'=>2], // PASIVO_CORRIENTE
            ['catalogo_cuenta_id'=>103,'concepto_id'=>3], // INVENTARIO
            ['catalogo_cuenta_id'=>104,'concepto_id'=>5], // VENTAS_NETAS
            ['catalogo_cuenta_id'=>105,'concepto_id'=>7], // UTILIDAD_NETA
            ['catalogo_cuenta_id'=>106,'concepto_id'=>8], // TOTAL_PATRIMONIO
        ];

        foreach ($map as $m) {
            DB::table('cuenta_concepto')->updateOrInsert(
                [
                    'catalogo_cuenta_id' => $m['catalogo_cuenta_id'],
                    'concepto_id'        => $m['concepto_id'],
                ],
                ['updated_at' => now(), 'created_at' => now()]
            );
        }
    }
}
