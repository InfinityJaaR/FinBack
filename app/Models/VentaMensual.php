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
        'fecha',
        'monto',
    ];

    protected $casts = [
        'monto' => 'decimal:2',
        'fecha' => 'date',
    ];

    /**
     * La venta mensual pertenece a una empresa (N:1).
     */
    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }
}