<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\CatalogoCuenta;
use App\Models\Periodo;

class EstadosYDetallesSeeder extends Seeder
{
    public function run(): void
    {
        // Empresas con catálogo completo
        $empresas = [1];

        // Periodos a crear (2022)
        $periodos = Periodo::whereIn('anio', [2022])->get();

        foreach ($empresas as $empresaId) {
            foreach ($periodos as $periodo) {
                $this->crearEstadosParaEmpresaYPeriodo($empresaId, $periodo->id);
            }
        }
    }

    private function crearEstadosParaEmpresaYPeriodo($empresaId, $periodoId): void
    {
        // Crear Balance General
        DB::table('estados')->updateOrInsert(
            [
                'empresa_id' => $empresaId,
                'periodo_id' => $periodoId,
                'tipo' => 'BALANCE'
            ],
            [
                'created_at' => now(),
                'updated_at' => now()
            ]
        );

        // Crear Estado de Resultados
        DB::table('estados')->updateOrInsert(
            [
                'empresa_id' => $empresaId,
                'periodo_id' => $periodoId,
                'tipo' => 'RESULTADOS'
            ],
            [
                'created_at' => now(),
                'updated_at' => now()
            ]
        );

        // Obtener los IDs de los estados creados
        $balanceId = DB::table('estados')->where([
            'empresa_id' => $empresaId,
            'periodo_id' => $periodoId,
            'tipo' => 'BALANCE'
        ])->value('id');

        $resultadosId = DB::table('estados')->where([
            'empresa_id' => $empresaId,
            'periodo_id' => $periodoId,
            'tipo' => 'RESULTADOS'
        ])->value('id');

        // Limpiar detalles anteriores
        DB::table('detalles_estado')->whereIn('estado_id', [$balanceId, $resultadosId])->delete();

        // Obtener cuentas de Balance General (tipo 1, 2, 3)
        $cuentasBalance = CatalogoCuenta::where('empresa_id', $empresaId)
            ->whereIn('estado_financiero', ['BALANCE_GENERAL'])
            ->where('es_calculada', false)
            ->orderBy('codigo')
            ->limit(10)
            ->get();

        // Obtener cuentas de Estado de Resultados (tipo 4, 5, 6, 7)
        $cuentasResultados = CatalogoCuenta::where('empresa_id', $empresaId)
            ->whereIn('estado_financiero', ['ESTADO_RESULTADOS'])
            ->where('es_calculada', false)
            ->orderBy('codigo')
            ->limit(10)
            ->get();

        // Insertar detalles para Balance General
        $detallesBalance = [];
        foreach ($cuentasBalance as $cuenta) {
            $detallesBalance[] = [
                'estado_id' => $balanceId,
                'catalogo_cuenta_id' => $cuenta->id,
                'monto' => rand(10000, 100000) * 100, // Montos aleatorios entre 1M y 10M
                'created_at' => now(),
                'updated_at' => now()
            ];
        }

        // Insertar detalles para Estado de Resultados
        $detallesResultados = [];
        foreach ($cuentasResultados as $cuenta) {
            $detallesResultados[] = [
                'estado_id' => $resultadosId,
                'catalogo_cuenta_id' => $cuenta->id,
                'monto' => rand(5000, 50000) * 100, // Montos aleatorios entre 500K y 5M
                'created_at' => now(),
                'updated_at' => now()
            ];
        }

        // Insertar todos los detalles
        if (!empty($detallesBalance)) {
            DB::table('detalles_estado')->insert($detallesBalance);
        }

        if (!empty($detallesResultados)) {
            DB::table('detalles_estado')->insert($detallesResultados);
        }
    }
}
