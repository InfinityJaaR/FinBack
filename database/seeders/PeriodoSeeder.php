<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PeriodoSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('periodos')->updateOrInsert(
            ['id' => 1],
            [
                'anio'         => 2024,
                'fecha_inicio' => '2024-01-01',
                'fecha_fin'    => '2024-12-31',
                'created_at'   => now(),
                'updated_at'   => now(),
            ]
        );
    }
}
