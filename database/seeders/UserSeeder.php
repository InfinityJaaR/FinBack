<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Rol;
use App\Models\User;
use App\Models\Empresa;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = Rol::all();
        
        // Obtener algunas empresas para asignar solo a analistas financieros
        $empresas = Empresa::limit(3)->get();

        foreach ($roles as $index => $rol) {
            // Solo los Analistas Financieros tienen empresa asignada
            $empresaId = null;
            
            // Asignar empresa únicamente a usuarios con rol "Analista Financiero"
            if ($rol->name === 'Analista Financiero' && $empresas->isNotEmpty()) {
                // Asignar empresa de forma rotativa
                $empresaId = $empresas[$index % $empresas->count()]->id;
            }

            $usuario = User::create([
                'name' => $rol->name . ' test',
                'email' => strtolower(str_replace(' ', '_', $rol->name)) . '@test.com',
                'active' => true,
                'empresa_id' => $empresaId,
                'created_at' => now(),
                'updated_at' => now(),
                // No se agrega contraseña
            ]);
            // Relacionar usuario con el rol
            $usuario->roles()->sync([$rol->id]);
        }
    }
}
