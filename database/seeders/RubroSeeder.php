<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Rubro;
use Illuminate\Support\Facades\DB;

// Este seeder ahora también puede insertar benchmarks iniciales (migración desde los antiguos
// campos promedio_*). Para evitar dependencias frágiles en claves foráneas, buscamos por código
// de ratio y usamos updateOrInsert en la tabla `benchmarks_rubro`.

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
            $data = $rubro;
            // No pasamos campos de promedio al crear el rubro (ahora van a benchmarks)
            $promedioPrueba = $data['promedio_prueba_acida'] ?? null;
            unset($data['promedio_prueba_acida']);

            $created = Rubro::firstOrCreate(['codigo' => $data['codigo']], $data);

            // Sembrar benchmarks para cada definición de ratio existente.
            // Usamos una tabla de valores por defecto por código; si el arreglo original incluía
            // un promedio específico (p.ej. promedio_prueba_acida) lo tomamos para PRUEBA_ACIDA.
            $ratios = DB::table('ratios_definiciones')->pluck('id', 'codigo')->toArray();

            $defaults = [
                'RAZ_CORR' => 1.0,
                'PRUEBA_ACIDA' => $promedioPrueba ?? 1.0,
                'ROT_INV' => 6.0,
                'ROT_CXC' => 8.0,
                'RENTABILIDAD' => 0.08,
            ];

            foreach ($ratios as $codigo => $id) {
                $valor = $defaults[$codigo] ?? 0.0;
                DB::table('benchmarks_rubro')->updateOrInsert(
                    ['rubro_id' => $created->id, 'ratio_id' => $id],
                    ['valor_promedio' => $valor, 'fuente' => 'seeder_rubros', 'created_at' => now(), 'updated_at' => now()]
                );
            }
        }
    }
}