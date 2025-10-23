<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
   use HasFactory, HasApiTokens, Notifiable;

    protected $table = 'users';

    protected $fillable = [
        'name',
        'email',
        'password',
        'active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function roles()
    {
        return $this->belongsToMany(Rol::class, 'user_role', 'user_id', 'role_id');
    }
    /**
     * Determina si el usuario tiene una habilidad/permiso específico.
     * Sobrescribe el método can() para usar tu lógica de roles->permisos.
     */
    public function can($ability, $arguments = []): bool
    {
        // 1. Si la habilidad es una super-capacidad (ej. 'Administrador'),
        // 2. Obtiene todos los permisos del usuario a través de todos sus roles.
        $permisosDelUsuario = $this->roles()
            ->with('permisos') // Carga la relación de permisos dentro de cada rol
            ->get() // Ejecuta la consulta y obtiene los roles
            ->pluck('permisos') // Obtiene solo la colección de permisos de cada rol
            ->flatten() // Convierte la colección de colecciones en una sola colección
            ->pluck('name') // Extrae solo los nombres de los permisos
            ->unique(); // Asegura nombres únicos

        // 3. Verifica si la colección de permisos contiene el permiso solicitado ($ability).
        $tienePermiso = $permisosDelUsuario->contains($ability);

        // Si se encuentra el permiso, devuelve true.
        if ($tienePermiso) {
            return true;
        }

        // Si no se encuentra en tu lógica personalizada, puedes dejarlo en false 
        // o recurrir al método can() de la clase padre (Authenticatable) si usas Gates/Policies.
        return parent::can($ability, $arguments);
    }
}
