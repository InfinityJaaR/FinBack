<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Empresa;
use App\Models\BenchmarkRubro;

class Rubro extends Model
{
    use HasFactory;

    protected $fillable = [
        'codigo',
        'nombre',
        'descripcion',
    ];
    
    // Los promedios ahora se almacenan en `benchmarks_rubro` como filas por (rubro_id, ratio_id).
    protected $casts = [
        // Mantener solo casts relevantes al modelo Rubro.
    ];

    /**
     * Un rubro tiene muchas empresas.
     */
    public function empresas(): HasMany
    {
        return $this->hasMany(Empresa::class);
    }

    /**
     * Un rubro tiene muchos benchmarks de referencia.
     */
    public function benchmarks(): HasMany
    {
        return $this->hasMany(BenchmarkRubro::class);
    }
}