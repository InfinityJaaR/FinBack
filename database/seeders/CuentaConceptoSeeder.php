<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ConceptoFinanciero;
use App\Models\CatalogoCuenta;
use App\Models\CuentaConcepto;

class CuentaConceptoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * This seeder tries to map catalog accounts to conceptos_financieros using simple name/code patterns.
     * It's idempotent: uses firstOrCreate and logs unmapped concepts for manual review.
     */
    public function run()
    {
        // Define mapping rules: concepto_codigo => array of patterns to search in catalogo_cuentas.nombre or code-prefix
        // Puedes ajustar estos patrones o añadir entries en $manualMappings más abajo para forzar mapeos exactos.
        $mappings = [
            'ACT_COR' => ['ACTIVO CORRIENTE', 'ACTIVO CORR', 'ACTIVO COR'],
            'INVENTARIO' => ['INVENTARIO', 'STOCK'],
            'CXC' => ['CUENTAS POR COBRAR', 'CLIENTES', 'CXC'],
            'VENTAS_NETAS' => ['VENTAS NETAS', 'VENTAS'],
            'COSTO_VENTAS' => ['COSTO DE VENTAS', 'COSTO', 'CMV'],
            'EFECTIVO' => ['CAJA', 'EFECTIVO', 'BANCOS'],
            'ACT_TOTAL' => ['ACTIVO TOTAL', 'ACTIVO'],
            'PAS_COR' => ['PASIVO CORRIENTE', 'PASIVO'],
        ];

        // Manual mappings: si conoces IDs o códigos del catálogo que deben mapearse exactamente a un concepto,
        // agrégalos aquí en formato 'catalogo_codigo' => 'CONCEPTO_CODIGO'. Esto tiene prioridad sobre las heurísticas.
        // Ejemplo: '1405' => 'INVENTARIO'
        $manualMappings = [
            // '1405' => 'INVENTARIO',
        ];

        foreach ($mappings as $conceptoCodigo => $patterns) {
            $concepto = ConceptoFinanciero::where('codigo', $conceptoCodigo)->first();
            if (! $concepto) {
                $this->command->warn("Concepto con codigo {$conceptoCodigo} no existe. Skipping mapping.");
                continue;
            }

            $matched = 0;
            foreach ($patterns as $p) {
                // si el patrón parece un código (solo dígitos), buscar por prefijo de código
                if (preg_match('/^\d+$/', $p)) {
                    $accounts = CatalogoCuenta::where('codigo', 'like', $p . '%')->get();
                } else {
                    $accounts = CatalogoCuenta::where('nombre', 'like', "%{$p}%")->get();
                }

                foreach ($accounts as $acct) {
                    CuentaConcepto::firstOrCreate([
                        'concepto_financiero_id' => $concepto->id,
                        'catalogo_cuenta_id' => $acct->id,
                    ]);
                    $matched++;
                    $this->command->info("Mapeado concepto {$concepto->nombre_concepto} -> cuenta {$acct->codigo} : {$acct->nombre}");
                }
            }

            // Aplicar manualMappings (si existe alguna entrada con el codigo exacto del catálogo)
            foreach ($manualMappings as $catalogoCodigo => $conceptoCodigo) {
                if ($conceptoCodigo !== $concepto->codigo) continue;
                $acct = CatalogoCuenta::where('codigo', $catalogoCodigo)->first();
                if ($acct) {
                    CuentaConcepto::firstOrCreate([
                        'concepto_financiero_id' => $concepto->id,
                        'catalogo_cuenta_id' => $acct->id,
                    ]);
                    $matched++;
                    $this->command->info("(Manual) Mapeado concepto {$concepto->nombre_concepto} -> cuenta {$acct->codigo} : {$acct->nombre}");
                }
            }

            if ($matched === 0) {
                $this->command->warn("No se encontraron cuentas automáticas para concepto {$concepto->nombre_concepto}. Revisión manual requerida.");
            }
        }

        $this->command->info('Mapeos cuenta->concepto finalizados.');
    }
}
