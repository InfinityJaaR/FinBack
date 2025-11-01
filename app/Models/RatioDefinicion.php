<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class RatioDefinicion extends Model
{
    use HasFactory;
    protected $table = 'ratios_definiciones';
    
    protected $fillable = [
        'codigo',
        'nombre',
        'formula',
        'sentido',
    ];

    // ... (rest of fillable, casts, etc.)

    /**
     * Una definición de ratio tiene muchos valores calculados.
     */
    public function valores(): HasMany
    {
        return $this->hasMany(RatioValor::class, 'ratio_id');
    }

    /**
     * Una definición de ratio tiene muchos benchmarks por rubro.
     */
    public function benchmarks(): HasMany
    {
        return $this->hasMany(BenchmarkRubro::class, 'ratio_id');
    }

    // --- NUEVA RELACIÓN ---
    /**
     * Una definición de ratio se compone de varios conceptos financieros (N:M).
     */
    public function componentes(): HasMany
    {
        // Se usa la tabla pivote 'ratio_componentes'
        return $this->hasMany(RatioComponente::class, 'ratio_id');
                            
    }
}