<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Rol;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = Rol::all();

        foreach ($roles as $rol) {
            $usuario = User::create([
                'name' => $rol->name . ' test',
                'email' => strtolower(str_replace(' ', '_', $rol->name)) . '@test.com',
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
                // No se agrega contraseña
            ]);
            // Relacionar usuario con el rol
            $usuario->roles()->sync([$rol->id]);
        }
    }
}
