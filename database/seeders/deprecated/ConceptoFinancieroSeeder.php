<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ConceptoFinanciero;

class ConceptoFinancieroSeeder extends Seeder
{
    public function run(): void
    {
        // Conceptos básicos para liquidez y rentabilidad, necesarios para las fórmulas.
        $conceptos = [
            // Liquidez y Capital
            ['nombre_concepto' => 'ACTIVO_CORRIENTE', 'descripcion' => 'Activos que se convertirán en efectivo en un año.'],
            ['nombre_concepto' => 'PASIVO_CORRIENTE', 'descripcion' => 'Obligaciones que vencen en menos de un año.'],
            ['nombre_concepto' => 'INVENTARIO', 'descripcion' => 'Inventario total de bienes.'],
            ['nombre_concepto' => 'ACTIVO_TOTAL', 'descripcion' => 'Suma de activos corrientes y no corrientes.'],
            
            // Rentabilidad y Gastos
            ['nombre_concepto' => 'VENTAS_NETAS', 'descripcion' => 'Ingresos totales menos devoluciones y descuentos.'],
            ['nombre_concepto' => 'GASTOS_ADMINISTRACION_VENTA', 'descripcion' => 'Gastos operacionales y de venta.'],
            ['nombre_concepto' => 'UTILIDAD_NETA', 'descripcion' => 'Beneficio después de impuestos.'],
            ['nombre_concepto' => 'TOTAL_PATRIMONIO', 'descripcion' => 'Capital social más reservas y utilidades retenidas.'],
        ];

        foreach ($conceptos as $concepto) {
            ConceptoFinanciero::firstOrCreate($concepto);
        }
    }
}