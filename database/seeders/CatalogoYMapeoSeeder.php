<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CatalogoYMapeoSeeder extends Seeder
{
    const EMPRESA_ID = 1;

    public function run(): void
    {
        // Códigos que pertenecen al Balance General
        $BALANCE = ['1100','2100','1130','1000','3000','2000'];
        // Códigos que pertenecen al Estado de Resultados
        $RESULTADOS = ['5100','4000','5000'];

        // === 1) Crear/Actualizar catálogo ===
        // (No te preocupes por el estado_financiero aquí; lo calculamos abajo)
        $cuentas = [
            ['codigo'=>'1100','nombre'=>'Activo Corriente',  'tipo'=>'ACTIVO',     'es_calculada'=>0],
            ['codigo'=>'2100','nombre'=>'Pasivo Corriente',  'tipo'=>'PASIVO',     'es_calculada'=>0],
            ['codigo'=>'1130','nombre'=>'Inventario',        'tipo'=>'ACTIVO',     'es_calculada'=>0],
            ['codigo'=>'1000','nombre'=>'Activos totales',   'tipo'=>'ACTIVO',     'es_calculada'=>0],
            ['codigo'=>'5100','nombre'=>'Costo de ventas',   'tipo'=>'GASTO',      'es_calculada'=>0],
            ['codigo'=>'3000','nombre'=>'Patrimonio',        'tipo'=>'PATRIMONIO', 'es_calculada'=>0],
            ['codigo'=>'2000','nombre'=>'Pasivo total',      'tipo'=>'PASIVO',     'es_calculada'=>0],
            ['codigo'=>'4000','nombre'=>'Gastos totales',    'tipo'=>'GASTO',      'es_calculada'=>0],
            ['codigo'=>'5000','nombre'=>'Ingresos',          'tipo'=>'INGRESO',    'es_calculada'=>0],
        ];

        foreach ($cuentas as $c) {
            // Resolver estado_financiero
            $estadoFinanciero = in_array($c['codigo'], $BALANCE)
                ? 'BALANCE_GENERAL'
                : (in_array($c['codigo'], $RESULTADOS) ? 'ESTADO_RESULTADOS' : 'NINGUNO');

            DB::table('catalogo_cuentas')->updateOrInsert(
                [
                    'empresa_id' => self::EMPRESA_ID,
                    'codigo'     => $c['codigo'],
                ],
                [
                    'nombre'            => $c['nombre'],
                    'tipo'              => $c['tipo'],            // ACTIVO | PASIVO | PATRIMONIO | INGRESO | GASTO
                    'es_calculada'      => $c['es_calculada'],
                    'estado_financiero' => $estadoFinanciero,     // BALANCE_GENERAL | ESTADO_RESULTADOS | NINGUNO
                    'updated_at'        => now(),
                    'created_at'        => now(),
                ]
            );
        }

        // === 2) Mapeo automático a conceptos_financieros ===
        $conceptos = DB::table('conceptos_financieros')->pluck('id', 'codigo'); // ['ACT_COR'=>1, ...]
        $cuentasEmpresa = DB::table('catalogo_cuentas')
            ->where('empresa_id', self::EMPRESA_ID)
            ->pluck('id', 'codigo'); // ['1100'=>X, ...]

        $mapeos = [
            '1100' => 'ACT_COR',
            '2100' => 'PAS_COR',
            '1130' => 'INVENTARIO',
            '1000' => 'ACT_TOTAL',
            '5100' => 'COSTO_VENTAS',
            '3000' => 'PATRIMONIO',
            '2000' => 'PAS_TOTAL',
            '4000' => 'GASTOS_TOTALES',
            '5000' => 'INGRESOS',
        ];

        foreach ($mapeos as $cuentaCodigo => $conceptoCodigo) {
            $catalogoCuentaId = $cuentasEmpresa[$cuentaCodigo] ?? null;
            $conceptoId       = $conceptos[$conceptoCodigo] ?? null;

            if (!$catalogoCuentaId || !$conceptoId) {
                continue; // si algo falta, no intenta insertar
            }

            DB::table('cuenta_concepto')->updateOrInsert(
                [
                    'catalogo_cuenta_id' => $catalogoCuentaId,
                    'concepto_id'        => $conceptoId,
                ],
                [
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }
}
