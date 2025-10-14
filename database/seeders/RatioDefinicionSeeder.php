<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\RatioDefinicion;

class RatioDefinicionSeeder extends Seeder
{
    public function run(): void
    {
        $ratios = [
            [
                'codigo' => 'PRB_ACIDA',
                'nombre' => 'Prueba Ácida (Acid Test)',
                'formula' => '(Activo Corriente - Inventario) / Pasivo Corriente',
                'sentido' => 'MAYOR_MEJOR',
            ],
            [
                'codigo' => 'LIQ_CORR',
                'nombre' => 'Razón de Liquidez Corriente',
                'formula' => 'Activo Corriente / Pasivo Corriente',
                'sentido' => 'MAYOR_MEJOR',
            ],
            [
                'codigo' => 'MARG_OP',
                'nombre' => 'Margen Operacional',
                'formula' => 'Utilidad Operacional / Ventas Netas',
                'sentido' => 'MAYOR_MEJOR',
            ],
            [
                'codigo' => 'ROE',
                'nombre' => 'Rentabilidad sobre Patrimonio (ROE)',
                'formula' => 'Utilidad Neta / Patrimonio Total',
                'sentido' => 'MAYOR_MEJOR',
            ],
        ];

        foreach ($ratios as $ratio) {
            RatioDefinicion::firstOrCreate(['codigo' => $ratio['codigo']], $ratio);
        }
    }
}