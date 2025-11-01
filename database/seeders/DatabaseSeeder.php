<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            // Seguridad
            RolSeeder::class,
            PermisoSeeder::class,
            UserSeeder::class,
            // No se para que son xd
            RubroSeeder::class,
            RatioDefinicionSeeder::class,
            ConceptoFinancieroSeeder::class,
            EmpresaSeeder::class,
            VentaMensualSeeder::class,
            PeriodoSeeder::class,            
            CatalogoYMapeoSeeder::class,
            EstadosYDetallesSeeder::class,            
            RatioComponentesSeeder::class,
            // Aquí se llamarían los seeders dedatos transaccionales.
        ]);
    }
}