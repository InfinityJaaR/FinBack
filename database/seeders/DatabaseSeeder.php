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
        // ÚNICO LLAMADO A SEEDERS (Ordenado por dependencias)
        // ===================================
        $this->call([
            // 1. SEGURIDAD Y MAESTROS DE NIVEL 0 (Base sin dependencias)
            // Seguridad
            RolSeeder::class,
            PermisoSeeder::class,
            UserSeeder::class,
            
            // Maestros Base
            RubroSeeder::class,
            ConceptoFinancieroSeeder::class, // CLAVE: Necesario antes de RatioDefinicion
            
            // 2. MAESTROS DE NIVEL 1 (Datos dependientes y definiciones)
            // Depende de Rubro
            EmpresaSeeder::class, 
            
            // Depende de ConceptoFinanciero
            RatioDefinicionSeeder::class,
            
            // Maestros de datos contables
            PeriodoSeeder::class, 
            CatalogoYMapeoSeeder::class,
            
            // 3. DATOS TRANSACCIONALES DE PRUEBA (Dependen de Empresas, Conceptos y Periodos)
            // Dependen de Empresa y Periodo
            VentaMensualSeeder::class, 
            
            // Datos contables de prueba
            EstadosYDetallesSeeder::class, 
            
            // Componentes que usan las definiciones y los conceptos.
            RatioComponentesSeeder::class,
        ]);
    }
}