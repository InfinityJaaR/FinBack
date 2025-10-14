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
        // 1. Crea/Encuentra el Usuario de Prueba de forma segura
        User::firstOrCreate(
            ['email' => 'test@example.com'], // Criterio de búsqueda
            [
                'name' => 'Test User',
                // Usamos bcrypt() para generar un hash de contraseña válido
                'password' => bcrypt('password'), // ¡Importante: Usa una contraseña segura!
            ]
        );

        // 2. Llama al resto de los seeders de lógica de negocio
        $this->call([
            RubroSeeder::class,
            RatioDefinicionSeeder::class,
            ConceptoFinancieroSeeder::class,
            EmpresaSeeder::class,
            VentaMensualSeeder::class,
            // Aquí se llamarían los seeders de seguridad (UsuarioSeeder) y datos transaccionales.
        ]);
    }
}