<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RatioComponente extends Pivot
{
    protected $table = 'ratio_componentes';
    public $incrementing = false; // No tiene ID autoincremental
    //protected $primaryKey = ['ratio_id', 'concepto_id']; // Clave compuesta

    protected $fillable = [
        'ratio_id',
        'concepto_id',
        'rol',
        'orden',
        'sentido',
        'requiere_promedio',
    ];

    protected $casts = [
        'orden' => 'integer',
        'sentido' => 'integer',
        'requiere_promedio' => 'boolean',
    ];

    // Definición de las relaciones BelongsTo
    public function ratioDefinicion(): BelongsTo
    {
        return $this->belongsTo(RatioDefinicion::class, 'ratio_id');
    }

    public function conceptoFinanciero(): BelongsTo
    {
        return $this->belongsTo(ConceptoFinanciero::class, 'concepto_id');
    }
}