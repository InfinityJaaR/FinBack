<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Empresa;

class EmpresaSeeder extends Seeder
{
    /**
     * Seed the Empresa model using its factory.
     */
    public function run(): void
    {
        // Creamos 10 empresas de prueba.
        // Asegúrate de que RubroSeeder se haya ejecutado antes, ya que Empresa depende de Rubro.
        Empresa::factory(10)->create();
    }
}