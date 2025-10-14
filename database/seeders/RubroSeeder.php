<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Rubro;

class RubroSeeder extends Seeder
{
    public function run(): void
    {
        $rubros = [
            [
                'codigo' => 'MIN',
                'nombre' => 'Minería y Extracción',
                'descripcion' => 'Empresas dedicadas a la extracción de minerales.',
                'promedio_prueba_acida' => 0.85,
            ],
            [
                'codigo' => 'VEO',
                'nombre' => 'Venta de Equipo de Oficina',
                'descripcion' => 'Empresas dedicadas a la venta y distribución de equipos.',
                'promedio_prueba_acida' => 1.20,
            ],
            [
                'codigo' => 'CON',
                'nombre' => 'Construcción Civil',
                'descripcion' => 'Empresas dedicadas a la edificación e infraestructura.',
                'promedio_prueba_acida' => 0.70,
            ],
        ];

        foreach ($rubros as $rubro) {
            Rubro::firstOrCreate(['codigo' => $rubro['codigo']], $rubro);
        }
    }
}