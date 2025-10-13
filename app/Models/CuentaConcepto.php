<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CuentaConcepto extends Pivot
{
    protected $table = 'cuenta_concepto';
    public $incrementing = false; // No tiene ID autoincremental
    protected $primaryKey = ['catalogo_cuenta_id', 'concepto_id']; // Clave compuesta

    protected $fillable = [
        'catalogo_cuenta_id',
        'concepto_id',
    ];

    // Definición de las relaciones BelongsTo para acceder a las entidades
    public function catalogoCuenta(): BelongsTo
    {
        return $this->belongsTo(CatalogoCuenta::class);
    }

    public function conceptoFinanciero(): BelongsTo
    {
        return $this->belongsTo(ConceptoFinanciero::class, 'concepto_id');
    }
}