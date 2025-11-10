<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Empresa;
use App\Models\VentaMensual;

class VentaMensualCoherenteSeeder extends Seeder
{
    /**
     * Seed the VentaMensual model with coherent monthly series for each company.
     * Idempotente por (empresa_id, anio, mes).
     */
    public function run(): void
    {
        $empresas = Empresa::all();
        if ($empresas->isEmpty()) {
            $this->command->info('No companies found, skipping VentaMensualCoherenteSeeder.');
            return;
        }

        foreach ($empresas as $empresa) {
            // Choose a base and trend per company to create realistic patterns
            $base = rand(8_000, 50_000);
            // monthly growth between -1% and +3%
            $monthlyGrowth = (rand(-100, 300) / 10000.0);

            // Seasonality multipliers for 12 months (values around 0.85 - 1.15)
            $season = [];
            for ($m = 0; $m < 12; $m++) {
                $season[] = 0.9 + (rand(0, 60) / 1000.0); // 0.90 .. 1.06
            }

            // Rango solicitado: 2020-01 .. 2025-12
            $startYear = 2020;
            $endYear = 2025;
            $idx = 0; // para el crecimiento compuesto
            for ($year = $startYear; $year <= $endYear; $year++) {
                for ($month = 1; $month <= 12; $month++) {
                    // crecimiento compuesto mes a mes a partir del inicio
                    $trendFactor = pow(1 + $monthlyGrowth, $idx);
                    $seasonFactor = $season[($month - 1) % 12];
                    $noise = rand(-500, 500);
                    $monto = round(max(0, $base * $trendFactor * $seasonFactor + $noise), 2);

                    VentaMensual::updateOrCreate(
                        ['empresa_id' => $empresa->id, 'anio' => $year, 'mes' => $month],
                        ['monto' => $monto]
                    );
                    $idx++;
                }
            }

            $this->command->info("Seeded ventas for empresa_id={$empresa->id} ({$empresa->nombre})");
        }
    }
}
