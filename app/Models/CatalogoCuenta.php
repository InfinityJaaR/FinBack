<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany; // <--- NUEVO

class CatalogoCuenta extends Model
{
    use HasFactory;
    protected $table = 'catalogo_cuentas';

    protected $fillable = [
        'empresa_id',
        'codigo',
        'nombre',
        'tipo',
        'es_calculada',
    ];

    // ... (rest of fillable, casts, etc.)

    /**
     * La cuenta pertenece a una empresa (N:1).
     */
    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    /**
     * La cuenta puede ser mapeada en varios detalles de estados financieros (1:N).
     */
    public function detallesEstado(): HasMany
    {
        return $this->hasMany(DetalleEstado::class);
    }
    
    // --- NUEVA RELACIÓN ---
    /**
     * Una cuenta se mapea a uno o más conceptos financieros abstractos (N:M).
     */
    public function conceptos(): BelongsToMany
    {
        // Se usa la tabla pivote 'cuenta_concepto'
        return $this->belongsToMany(ConceptoFinanciero::class, 'cuenta_concepto', 'catalogo_cuenta_id', 'concepto_id');
    }
}