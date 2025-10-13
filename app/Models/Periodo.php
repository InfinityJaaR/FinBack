<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Periodo extends Model
{
    use HasFactory;

    protected $fillable = [
        'anio',
        'fecha_inicio',
        'fecha_fin',
    ];

    protected $dates = [
        'fecha_inicio',
        'fecha_fin',
    ];

    /**
     * Un periodo puede tener muchos estados financieros (Balance, Resultados).
     */
    public function estados(): HasMany
    {
        return $this->hasMany(Estado::class);
    }

    /**
     * Un periodo tiene muchos valores de ratios calculados.
     */
    public function ratiosValores(): HasMany
    {
        return $this->hasMany(RatioValor::class);
    }
}