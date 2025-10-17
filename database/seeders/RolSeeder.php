<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Rol;
use Illuminate\Support\Facades\DB;

class RolSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'name' => 'Administrador',
                'description' => 'Usuario con acceso total al sistema de seguridad',
            ],
            [
                'name' => 'Analista Financiero',
                'description' => 'Usuario con acceso a su empresa y a la información de su rubro',
            ],
            [
                'name' => 'Inversor',
                'description' => 'Usuario con acceso a lo resumido de los rubros',
            ],
        ];
        foreach ($roles as $role) {
            DB::table('roles')->updateOrInsert(
                ['name' => $role['name']],
                [
                    'description' => $role['description'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
