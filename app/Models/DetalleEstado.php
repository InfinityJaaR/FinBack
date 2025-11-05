<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetalleEstado extends Model
{
    use HasFactory;
    protected $table = 'detalles_estado';

    protected $fillable = [
        'estado_id',
        'catalogo_cuenta_id',
        'monto',
        'usar_en_ratios',
    ];

    protected $casts = [
        'monto' => 'decimal:2',
        'usar_en_ratios' => 'boolean',
    ];

    /**
     * El detalle pertenece a un encabezado de estado financiero (N:1).
     */
    public function estado(): BelongsTo
    {
        return $this->belongsTo(Estado::class);
    }

    /**
     * El detalle está asociado a una cuenta del catálogo de la empresa (N:1).
     */
    public function catalogoCuenta(): BelongsTo
    {
        return $this->belongsTo(CatalogoCuenta::class, 'catalogo_cuenta_id');
    }
}