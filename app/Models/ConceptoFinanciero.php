<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ConceptoFinanciero extends Model
{
    use HasFactory;
    protected $table = 'conceptos_financieros';

    protected $fillable = [
        'nombre_concepto',
        'descripcion',
    ];

    /**
     * Un concepto puede ser parte de la fórmula de muchos ratios (a través de la tabla pivote).
     */
    public function ratios(): BelongsToMany
    {
        return $this->belongsToMany(RatioDefinicion::class, 'ratio_componentes', 'concepto_id', 'ratio_id')
                    ->withPivot('rol', 'orden', 'requiere_promedio', 'sentido');
    }

    /**
     * Un concepto está mapeado a muchas cuentas específicas de las empresas (a través de la tabla pivote).
     */
    public function cuentas(): BelongsToMany
    {
        return $this->belongsToMany(CatalogoCuenta::class, 'cuenta_concepto', 'concepto_id', 'catalogo_cuenta_id');
    }
}