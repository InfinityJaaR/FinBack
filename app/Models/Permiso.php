<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Permiso extends Model
{
     use HasFactory;

    protected $table = 'permiso';

    protected $fillable = [
        'name',
        'description',
    ];

    /**
     * Relación con el modelo de Rol.
     */
    public function roles()
    {
        return $this->belongsToMany(Rol::class, 'role_permission', 'permission_id', 'role_id');
    }
}
