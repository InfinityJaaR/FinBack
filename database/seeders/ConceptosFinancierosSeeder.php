<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ConceptosFinancierosSeeder extends Seeder
{
    public function run(): void
    {
        $conceptos = [
            ['codigo' => 'ACT_COR',             'nombre_concepto' => 'Activo Corriente'],
            ['codigo' => 'PAS_COR',             'nombre_concepto' => 'Pasivo Corriente'],
            ['codigo' => 'EFECTIVO',            'nombre_concepto' => 'Efectivo y Equivalentes'],
            ['codigo' => 'INVENTARIO',          'nombre_concepto' => 'Inventario'],
            ['codigo' => 'COSTO_VENTAS',        'nombre_concepto' => 'Costo de Ventas'],
            ['codigo' => 'VENTAS_NETAS',        'nombre_concepto' => 'Ventas Netas'],
            ['codigo' => 'VENTAS_CREDITO_N',    'nombre_concepto' => 'Ventas a Crédito Netas'],
            ['codigo' => 'CXC',                 'nombre_concepto' => 'Cuentas por Cobrar'],
            ['codigo' => 'ACT_TOTAL',           'nombre_concepto' => 'Activo Total'],
            ['codigo' => 'PATRIMONIO',          'nombre_concepto' => 'Patrimonio'],
            ['codigo' => 'UTILIDAD_NETA',       'nombre_concepto' => 'Utilidad Neta'],
            ['codigo' => 'EBIT',                'nombre_concepto' => 'EBIT'],
            ['codigo' => 'GASTOS_INTERESES',    'nombre_concepto' => 'Gastos por Intereses'],
            ['codigo' => 'PAS_TOTAL',           'nombre_concepto' => 'Pasivo Total'],
            ['codigo' => 'GASTOS_TOTALES',      'nombre_concepto' => 'Gastos Totales'],
            ['codigo' => 'INGRESOS',            'nombre_concepto' => 'Ingresos'],
        ];

        foreach ($conceptos as $c) {
            DB::table('conceptos_financieros')->updateOrInsert(
                ['codigo' => $c['codigo']],
                [
                    'nombre_concepto' => $c['nombre_concepto'],
                    'updated_at'      => now(),
                    'created_at'      => now(),
                ]
            );
        }
    }
}
