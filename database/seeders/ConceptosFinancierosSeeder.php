<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ConceptoFinanciero;

class ConceptosFinancierosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * This seeder is idempotent: it uses firstOrCreate so it can be run repeatedly.
     */
    public function run()
    {
        $conceptos = [
            ['nombre_concepto' => 'Activo Corriente', 'codigo' => 'ACT_COR'],
            ['nombre_concepto' => 'Pasivo Corriente', 'codigo' => 'PAS_COR'],
            ['nombre_concepto' => 'Efectivo y Equivalentes de Efectivo', 'codigo' => 'EFECTIVO'],
            ['nombre_concepto' => 'Inventario', 'codigo' => 'INVENTARIO'],
            ['nombre_concepto' => 'Costo de Ventas', 'codigo' => 'COSTO_VENTAS'],
            ['nombre_concepto' => 'Ventas Netas', 'codigo' => 'VENTAS_NETAS'],
            ['nombre_concepto' => 'Ventas a Crédito Netas', 'codigo' => 'VENTAS_CREDITO_NETAS'],
            ['nombre_concepto' => 'Cuentas por Cobrar', 'codigo' => 'CXC'],
            ['nombre_concepto' => 'Activo Total', 'codigo' => 'ACT_TOTAL'],
            ['nombre_concepto' => 'Patrimonio', 'codigo' => 'PATRIMONIO'],
            ['nombre_concepto' => 'Utilidad Neta', 'codigo' => 'UTILIDAD_NETA'],
            ['nombre_concepto' => 'EBIT', 'codigo' => 'EBIT'],
            ['nombre_concepto' => 'Gastos por Intereses', 'codigo' => 'GASTOS_INTERESES'],
        ];

        foreach ($conceptos as $c) {
            ConceptoFinanciero::firstOrCreate(
                ['codigo' => $c['codigo']],
                ['nombre_concepto' => $c['nombre_concepto']]
            );
            $this->command->info("Concepto registrado/confirmado: {$c['nombre_concepto']}");
        }

        $this->command->info('Conceptos financieros sembrados.');
    }
}
