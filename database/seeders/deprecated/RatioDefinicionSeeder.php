<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\RatioDefinicion;
use App\Models\ConceptoFinanciero;

class RatioDefinicionSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Obtener los IDs de los conceptos financieros CLAVE
        $conceptos = ConceptoFinanciero::pluck('id', 'nombre_concepto');

        // Estructura de Ratios: [Datos_Base, Componentes_Pivot]
        $dataRatios = [
            // PRUEBA ÁCIDA: (AC - INVENTARIO) / PC. NO requiere promedio en sus componentes.
            'PRB_ACIDA' => [
                'base' => [
                    'codigo' => 'PRB_ACIDA',
                    'nombre' => 'Prueba Ácida (Acid Test)',
                    'formula' => '(Activo Corriente - Inventario) / Pasivo Corriente',
                    'sentido' => 'MAYOR_MEJOR',
                ],
                'componentes' => [
                    // Concepto ID => [rol, orden, requiere_promedio]
                    $conceptos['ACTIVO_CORRIENTE'] => ['rol' => 'NUMERADOR', 'orden' => 1, 'requiere_promedio' => false],
                    $conceptos['INVENTARIO'] => ['rol' => 'OPERANDO', 'orden' => 2, 'requiere_promedio' => false],
                    $conceptos['PASIVO_CORRIENTE'] => ['rol' => 'DENOMINADOR', 'orden' => 3, 'requiere_promedio' => false],
                ],
            ],

            // ROTACIÓN DE INVENTARIOS: Costo de Ventas / Inventario Promedio. Inventario SI requiere promedio.
            'ROT_INV' => [
                'base' => [
                    'codigo' => 'ROT_INV',
                    'nombre' => 'Rotación de Inventarios',
                    'formula' => 'Costo de Ventas / Inventario Promedio',
                    'sentido' => 'MAYOR_MEJOR',
                ],
                'componentes' => [
                    // Costo de Ventas (Asumimos que existe este concepto)
                    // NOTA: Si Costo de Ventas es una cuenta de flujo, NO requiere promedio.
                    $conceptos['GASTOS_ADMINISTRACION_VENTA'] => ['rol' => 'NUMERADOR', 'orden' => 1, 'requiere_promedio' => false], 
                    // Inventario: Es una cuenta de STOCK, SÍ requiere promedio para Rotación.
                    $conceptos['INVENTARIO'] => ['rol' => 'DENOMINADOR', 'orden' => 2, 'requiere_promedio' => true], 
                ],
            ],
            
            // RAZÓN CORRIENTE: AC / PC. NO requiere promedio.
            'LIQ_CORR' => [
                'base' => [
                    'codigo' => 'LIQ_CORR',
                    'nombre' => 'Razón de Liquidez Corriente',
                    'formula' => 'Activo Corriente / Pasivo Corriente',
                    'sentido' => 'MAYOR_MEJOR',
                ],
                'componentes' => [
                    $conceptos['ACTIVO_CORRIENTE'] => ['rol' => 'NUMERADOR', 'orden' => 1, 'requiere_promedio' => false],
                    $conceptos['PASIVO_CORRIENTE'] => ['rol' => 'DENOMINADOR', 'orden' => 2, 'requiere_promedio' => false],
                ],
            ],
            // ... (otros ratios)
        ];

        // 2. Insertar los datos y adjuntar los componentes
        foreach ($dataRatios as $key => $data) {
            
            // 2a. Crear o encontrar la Definición del Ratio
            $ratio = RatioDefinicion::firstOrCreate(['codigo' => $data['base']['codigo']], $data['base']);

            // 2b. Preparar los datos del pivote para sync()
            $componentesParaSync = [];
            foreach ($data['componentes'] as $conceptoId => $pivotData) {
                // El formato de sync es [id_relacionado => [campo_pivote => valor]]
                $componentesParaSync[$conceptoId] = $pivotData;
            }

            // 2c. Sincronizar los componentes en la tabla pivote ratio_componentes
            $ratio->componentes()->sync($componentesParaSync);
        }
    }
}
