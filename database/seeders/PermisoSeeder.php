<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Rol;
use App\Models\Permiso;
use Illuminate\Support\Facades\DB;

class PermisoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
         $permisos = [
            // Seguridad
            ['name' => 'manage_users', 'description' => 'Gestionar usuarios'],
            
            // Gestión de Empresas
            ['name' => 'gestionar_rubros', 'description' => 'Permite crear, editar y eliminar rubros empresariales.'],
            ['name' => 'gestionar_empresas', 'description' => 'Permite crear, editar y eliminar empresas.'],
            ['name' => 'gestionar_ratios_definicion', 'description' => 'Permite crear, editar y eliminar definiciones de ratios financieros.'],
            ['name' => 'ver_comparaciones_internas', 'description' => 'Puede ver la vista de comparaciones internas de ratios.'],
            ['name' => 'ver_empresas', 'description' => 'Permite ver empresas sin modificarlas.'],


            //Gestión de datos financieros
            ['name' => 'gestionar_catalogo_cuentas', 'description' => 'Permite cargar, editar y eliminar catálogo de cuentas contables de las empresas.'],

            //Para ratios
            ['name' => 'ver_ratios', 'description' => 'Permite consultar los ratios financieros de una empresa.'],
            ['name' => 'calcular_ratios', 'description' => 'Permite generar ratios financieros para una empresa.'],


            //Analisis y reportes

            //Proyección de Ventas
        ];

        foreach ($permisos as $permiso) {
            Permiso::updateOrCreate(
                ['name' => $permiso['name']],
                [
                    'description' => $permiso['description'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        // Asignar permisos a roles
        $rolesPermisos = [
            'Administrador' => [
                'manage_users',
                'gestionar_rubros',
                'gestionar_empresas',
                'gestionar_ratios_definicion',
                'gestionar_catalogo_cuentas',
                'ver_ratios',
                'calcular_ratios',
                'ver_comparaciones_internas',
                'ver_empresas',
            ],
            'Analista Financiero' => [
                // Agregar permisos específicos para Analista Financiero
                'gestionar_catalogo_cuentas',
                'ver_ratios',
                'calcular_ratios',
                'ver_comparaciones_internas',
                'ver_empresas',
            ],
            'Inversor' => [
                // Agregar permisos específicos para Inversor
                'ver_ratios',                
            ],

        ];

        foreach ($rolesPermisos as $rolNombre => $permisosNombres) {
            $rol = Rol::where('name', $rolNombre)->first();
            if ($rol) {
                $permisoIds = Permiso::whereIn('name', $permisosNombres)->pluck('id')->toArray();
                foreach ($permisoIds as $permisoId) {
                    // Inserta en la tabla intermedia con timestamps
                    DB::table('role_permission')->updateOrInsert(
                        ['role_id' => $rol->id, 'permission_id' => $permisoId],
                        ['created_at' => now(), 'updated_at' => now()]
                    );
                }
            }
        }
    }
}
