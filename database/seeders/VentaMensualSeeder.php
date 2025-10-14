<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Empresa;
use App\Models\VentaMensual;

class VentaMensualSeeder extends Seeder
{
    /**
     * Seed the VentaMensual model using its factory for all existing companies.
     */
    public function run(): void
    {
        // 1. Obtener todas las empresas existentes.
        $empresas = Empresa::all();

        // 2. Para cada empresa, generar 12 registros de ventas mensuales históricos.
        $empresas->each(function ($empresa) {
            // Usa el factory para crear 12 meses de datos,
            // vinculándolos a la empresa actual.
            VentaMensual::factory(12)->create([
                'empresa_id' => $empresa->id,
            ]);
        });
    }
}