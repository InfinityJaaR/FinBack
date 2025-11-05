<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PeriodoSeeder extends Seeder
{
    public function run(): void
    {
        $periodos = [
            [
                'id' => 1,
                'anio' => 2022,
                'fecha_inicio' => '2022-01-01',
                'fecha_fin' => '2022-12-31',
            ],
            [
                'id' => 2,
                'anio' => 2023,
                'fecha_inicio' => '2023-01-01',
                'fecha_fin' => '2023-12-31',
            ],
            [
                'id' => 3,
                'anio' => 2024,
                'fecha_inicio' => '2024-01-01',
                'fecha_fin' => '2024-12-31',
            ],
            [
                'id' => 4,
                'anio' => 2025,
                'fecha_inicio' => '2025-01-01',
                'fecha_fin' => '2025-12-31',
            ],
        ];

        foreach ($periodos as $periodo) {
            DB::table('periodos')->updateOrInsert(
                ['id' => $periodo['id']],
                [
                    'anio' => $periodo['anio'],
                    'fecha_inicio' => $periodo['fecha_inicio'],
                    'fecha_fin' => $periodo['fecha_fin'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
