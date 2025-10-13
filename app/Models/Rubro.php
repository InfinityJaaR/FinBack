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
        'promedio_prueba_acida',
        'promedio_liquidez_corriente',
        'promedio_apalancamiento',
        'promedio_rentabilidad',
    ];

    protected $casts = [
        // Asegura que los campos de benchmark se manejen como flotantes/decimales en PHP.
        'promedio_prueba_acida' => 'decimal:2',
        'promedio_liquidez_corriente' => 'decimal:2',
        'promedio_apalancamiento' => 'decimal:2',
        'promedio_rentabilidad' => 'decimal:2',
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