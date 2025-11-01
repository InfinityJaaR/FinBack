<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RatioValor extends Model
{
    use HasFactory;
    protected $table = 'ratios_valores';

    protected $fillable = [
        'empresa_id',
        'periodo_id',
        'ratio_id',
        'valor',
        'fuente',
    ];

    protected $casts = [
        'valor' => 'decimal:2',
    ];

    /**
     * El valor pertenece a una empresa (N:1).
     */
    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    /**
     * El valor pertenece a un periodo (N:1).
     */
    public function periodo(): BelongsTo
    {
        return $this->belongsTo(Periodo::class);
    }

    /**
     * El valor corresponde a una definición de ratio (N:1).
     */
    public function ratioDefinicion(): BelongsTo
    {
        return $this->belongsTo(RatioDefinicion::class, 'ratio_id');
    }
}