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
            [
                'codigo' => 'RENTABILIDAD',
                'nombre' => 'Rentabilidad sobre Activos (ROA)',
                'formula' => 'Utilidad Neta / Activo Total',
                'sentido' => 'MAYOR_MEJOR',
                'categoria' => 'RENTABILIDAD',
                'multiplicador' => 1.0,
                'is_protected' => true,
                'componentes' => [
                    ['concepto_codigo' => 'UTILIDAD_NETA', 'rol' => 'NUMERADOR', 'orden' => 1, 'requiere_promedio' => false, 'sentido' => 1],
                    ['concepto_codigo' => 'ACT_TOTAL', 'rol' => 'DENOMINADOR', 'orden' => 1, 'requiere_promedio' => false, 'sentido' => 1],
                ],
            ],
        ];

        foreach ($definitions as $def) {
            // Guardamos la definición principal. La columna nueva es
            // `multiplicador_resultado` (migrada desde `multiplicador`).
            $ratio = RatioDefinicion::updateOrCreate(
                ['codigo' => $def['codigo']],
                [
                    'nombre' => $def['nombre'],
                    'formula' => $def['formula'],
                    'sentido' => $def['sentido'],
                    'categoria' => $def['categoria'],
                    'multiplicador_resultado' => $def['multiplicador'] ?? 1.0,
                    'is_protected' => $def['is_protected'] ?? false,
                ]
            );

            // Build componentes sync payload keyed by concepto_id.
            // Convertimos el viejo `sentido` numérico a `operacion` textual
            // (1 => ADD, -1 => SUB). Añadimos `factor` por defecto 1.0.
            $componentesData = [];
            foreach ($def['componentes'] as $c) {
                $concepto = ConceptoFinanciero::where('codigo', $c['concepto_codigo'])->first();
                if (! $concepto) {
                    $this->command->warn("Concepto con codigo {$c['concepto_codigo']} no encontrado, saltando componente.");
                    continue;
                }

                // Mapear sentido numérico a operación textual
                $operacion = null;
                if (isset($c['sentido'])) {
                    if ($c['sentido'] === 1) $operacion = 'ADD';
                    elseif ($c['sentido'] === -1) $operacion = 'SUB';
                }
                // Si no hay mapping, por seguridad asumimos ADD para NUMERADOR/DENOMINADOR
                if (! $operacion) {
                    $operacion = ($c['rol'] === 'OPERANDO' && ($c['sentido'] ?? 0) === -1) ? 'SUB' : 'ADD';
                }

                $componentesData[$concepto->id] = [
                    'rol' => $c['rol'],
                    'orden' => $c['orden'],
                    'requiere_promedio' => $c['requiere_promedio'],
                    'operacion' => $operacion,
                    'factor' => $c['factor'] ?? 1.0,
                ];
            }

            if (! empty($componentesData)) {
                $ratio->componentes()->sync($componentesData);
                $this->command->info("Ratio {$ratio->codigo} sincronizado con " . count($componentesData) . " componentes.");
            } else {
                $this->command->warn("Ratio {$ratio->codigo} creado pero sin componentes sincronizados.");
            }
        }

        $this->command->info('Definiciones de ratios sembradas/actualizadas (plural, operacion/factor).');
    }
}
