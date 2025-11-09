<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VentaMensual extends Model
{
    use HasFactory;
    protected $table = 'ventas_mensuales';

    protected $fillable = [
        'empresa_id',
        'anio',
        'mes',
        'monto',
    ];

    protected $casts = [
        'monto' => 'decimal:2',
    ];

    /**
     * Accessor: periodo AAAA-MM
     */
    public function getPeriodoAttribute(): string
    {
        $a = $this->anio;
        $m = $this->mes;
        return sprintf('%04d-%02d', $a, $m);
    }

    /**
     * La venta mensual pertenece a una empresa (N:1).
     */
    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }
}