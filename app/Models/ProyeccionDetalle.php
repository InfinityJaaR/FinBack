<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProyeccionDetalle extends Model
{
    use HasFactory;

    protected $table = 'proyecciones_detalle';

    protected $fillable = [
        'proyeccion_id',
        'fecha_proyectada',
        'monto_proyectado',
    ];

    protected $casts = [
        'fecha_proyectada' => 'date',
        'monto_proyectado' => 'decimal:2',
    ];

    /**
     * El detalle pertenece a una proyección.
     */
    public function proyeccion(): BelongsTo
    {
        return $this->belongsTo(Proyeccion::class, 'proyeccion_id');
    }
}
