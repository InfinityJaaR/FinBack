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
        'anio',
        'mes',
        'monto_proyectado',
    ];

    protected $casts = [
        'monto_proyectado' => 'decimal:2',
    ];

    /**
     * Accessor: periodo AAAA-MM (proyección)
     */
    public function getPeriodoAttribute(): string
    {
        $a = $this->anio;
        $m = $this->mes;
        return sprintf('%04d-%02d', $a, $m);
    }

    /**
     * El detalle pertenece a una proyección.
     */
    public function proyeccion(): BelongsTo
    {
        return $this->belongsTo(Proyeccion::class, 'proyeccion_id');
    }
}
