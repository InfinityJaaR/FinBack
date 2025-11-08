<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Empresa;
use App\Models\VentaMensual;
use Carbon\Carbon;

class VentaMensualCoherenteSeeder extends Seeder
{
    /**
     * Seed the VentaMensual model with coherent monthly series for each company.
     * This seeder is idempotent: uses updateOrCreate by (empresa_id, fecha) to avoid duplicates.
     */
    public function run(): void
    {
        $empresas = Empresa::all();
        if ($empresas->isEmpty()) {
            $this->command->info('No companies found, skipping VentaMensualCoherenteSeeder.');
            return;
        }

        // Generate data for the fixed year 2025 (Jan..Dec)
        $months = 12;
        $fixedYear = 2025;
        // End date is December of fixed year
        $end = Carbon::create($fixedYear, 12, 1);

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

            // iterate from oldest (Jan) to newest (Dec)
            for ($i = 0; $i < $months; $i++) {
                $fecha = Carbon::create($fixedYear, $i + 1, 1)->toDateString();

                // index from 0..months-1 (older -> newer)
                $idx = $i;

                // Apply compound growth
                $trendFactor = pow(1 + $monthlyGrowth, $idx);

                // season by month
                $monthIndex = intval(Carbon::parse($fecha)->month) - 1;
                $seasonFactor = $season[$monthIndex % 12];

                // small random noise
                $noise = rand(-500, 500);

                $monto = round(max(0, $base * $trendFactor * $seasonFactor + $noise), 2);

                VentaMensual::updateOrCreate(
                    ['empresa_id' => $empresa->id, 'fecha' => $fecha],
                    ['monto' => $monto]
                );
            }

            $this->command->info("Seeded ventas for empresa_id={$empresa->id} ({$empresa->nombre})");
        }
    }
}
