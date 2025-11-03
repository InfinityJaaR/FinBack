<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\RatioDefinicion;
use App\Models\ConceptoFinanciero;

class RatioDefinicionesSeeder extends Seeder
{
    /**
     * Seed some canonical ratio definitions (idempotent).
     */
    public function run()
    {
        $definitions = [
            [
                'codigo' => 'RAZ_CORR',
                'nombre' => 'Razón Corriente',
                'formula' => 'Activo Corriente / Pasivo Corriente',
                'sentido' => 'MAYOR_MEJOR',
                'categoria' => 'LIQUIDEZ',
                'multiplicador' => 1.0,
                'is_protected' => true,
                'componentes' => [
                    ['concepto_codigo' => 'ACT_COR', 'rol' => 'NUMERADOR', 'orden' => 1, 'requiere_promedio' => false, 'sentido' => 1],
                    ['concepto_codigo' => 'PAS_COR', 'rol' => 'DENOMINADOR', 'orden' => 1, 'requiere_promedio' => false, 'sentido' => 1],
                ],
            ],
            [
                'codigo' => 'PRUEBA_ACIDA',
                'nombre' => 'Prueba Ácida',
                'formula' => '(Activo Corriente - Inventario) / Pasivo Corriente',
                'sentido' => 'MAYOR_MEJOR',
                'categoria' => 'LIQUIDEZ',
                'multiplicador' => 1.0,
                'is_protected' => true,
                'componentes' => [
                    ['concepto_codigo' => 'ACT_COR', 'rol' => 'NUMERADOR', 'orden' => 1, 'requiere_promedio' => false, 'sentido' => 1],
                    ['concepto_codigo' => 'INVENTARIO', 'rol' => 'OPERANDO', 'orden' => 2, 'requiere_promedio' => false, 'sentido' => -1],
                    ['concepto_codigo' => 'PAS_COR', 'rol' => 'DENOMINADOR', 'orden' => 1, 'requiere_promedio' => false, 'sentido' => 1],
                ],
            ],
            [
                'codigo' => 'ROT_INV',
                'nombre' => 'Rotación de Inventarios',
                'formula' => 'Costo de Ventas / Inventario Promedio',
                'sentido' => 'MAYOR_MEJOR',
                'categoria' => 'EFICIENCIA',
                'multiplicador' => 1.0,
                'is_protected' => true,
                'componentes' => [
                    ['concepto_codigo' => 'COSTO_VENTAS', 'rol' => 'NUMERADOR', 'orden' => 1, 'requiere_promedio' => false, 'sentido' => 1],
                    ['concepto_codigo' => 'INVENTARIO', 'rol' => 'DENOMINADOR', 'orden' => 1, 'requiere_promedio' => true, 'sentido' => 1],
                ],
            ],
            [
                'codigo' => 'ROT_CXC',
                'nombre' => 'Rotación Cuentas por Cobrar',
                'formula' => 'Ventas Netas / Cuentas por Cobrar Promedio',
                'sentido' => 'MAYOR_MEJOR',
                'categoria' => 'EFICIENCIA',
                'multiplicador' => 1.0,
                'is_protected' => true,
                'componentes' => [
                    ['concepto_codigo' => 'VENTAS_NETAS', 'rol' => 'NUMERADOR', 'orden' => 1, 'requiere_promedio' => false, 'sentido' => 1],
                    ['concepto_codigo' => 'CXC', 'rol' => 'DENOMINADOR', 'orden' => 1, 'requiere_promedio' => true, 'sentido' => 1],
                ],
            ],
        ];

        foreach ($definitions as $def) {
            $ratio = RatioDefinicion::updateOrCreate(
                ['codigo' => $def['codigo']],
                [
                    'nombre' => $def['nombre'],
                    'formula' => $def['formula'],
                    'sentido' => $def['sentido'],
                    'categoria' => $def['categoria'],
                    'multiplicador' => $def['multiplicador'] ?? 1.0,
                    'is_protected' => $def['is_protected'] ?? false,
                ]
            );

            // Build componentes sync payload keyed by concepto_id
            $componentesData = [];
            foreach ($def['componentes'] as $c) {
                $concepto = ConceptoFinanciero::where('codigo', $c['concepto_codigo'])->first();
                if (! $concepto) {
                    $this->command->warn("Concepto con codigo {$c['concepto_codigo']} no encontrado, saltando componente.");
                    continue;
                }
                $componentesData[$concepto->id] = [
                    'rol' => $c['rol'],
                    'orden' => $c['orden'],
                    'requiere_promedio' => $c['requiere_promedio'],
                    'sentido' => $c['sentido'],
                ];
            }

            if (! empty($componentesData)) {
                $ratio->componentes()->sync($componentesData);
                $this->command->info("Ratio {$ratio->codigo} sincronizado con " . count($componentesData) . " componentes.");
            } else {
                $this->command->warn("Ratio {$ratio->codigo} creado pero sin componentes sincronizados.");
            }
        }

        $this->command->info('Definiciones de ratios sembradas/actualizadas.');
    }
}
