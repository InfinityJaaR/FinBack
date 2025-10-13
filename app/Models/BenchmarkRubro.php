<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BenchmarkRubro extends Model
{
    use HasFactory;
    protected $table = 'benchmarks_rubro';

    protected $fillable = [
        'rubro_id',
        'ratio_id',
        'anio',
        'valor_promedio',
        'fuente',
    ];

    protected $casts = [
        'valor_promedio' => 'decimal:6',
        'anio' => 'integer',
    ];

    /**
     * El benchmark pertenece a un rubro (N:1).
     */
    public function rubro(): BelongsTo
    {
        return $this->belongsTo(Rubro::class);
    }

    /**
     * El benchmark se aplica a una definición de ratio (N:1).
     */
    public function ratioDefinicion(): BelongsTo
    {
        return $this->belongsTo(RatioDefinicion::class, 'ratio_id');
    }
}