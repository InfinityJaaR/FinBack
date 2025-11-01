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
        // ===================================
        // 1. SEGURIDAD Y MAESTROS DE NIVEL 0 (Datos base)
        // ===================================
        $this->call([
            // Seguridad
            RolSeeder::class,
            PermisoSeeder::class,
            UserSeeder::class,
            

            RubroSeeder::class,
            // CLAVE: Los conceptos deben existir antes de que se definan los ratios
            ConceptoFinancieroSeeder::class, 
        ]);

        // ===================================
        // 2. MAESTROS DE NIVEL 1 (Datos dependientes)
        // ===================================
        $this->call([
            // Depende de Rubro
            EmpresaSeeder::class, 
            
            // Depende de Concepto Financiero (para buscar IDs)
            RatioDefinicionSeeder::class,
        ]);
        
        // ===================================
        // 3. DATOS TRANSACCIONALES DE PRUEBA
        // ===================================
        $this->call([
            // Dependen de Empresa
            VentaMensualSeeder::class, 
            // Aquí llamarías a los seeders de datos contables (EE.FF. de prueba)
            // DetallesEstadoSeeder::class, 
        ]);
    }
}
