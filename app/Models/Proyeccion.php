<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Proyeccion extends Model
{
    use HasFactory;

    protected $table = 'proyecciones';

    protected $fillable = [
        'empresa_id',
        'user_id',
        'metodo_usado',
        'periodo_proyectado',
    ];

    /**
     * Una proyección tiene muchos detalles (12 meses típicamente).
     */
    public function detalles(): HasMany
    {
        return $this->hasMany(ProyeccionDetalle::class, 'proyeccion_id');
    }
}
