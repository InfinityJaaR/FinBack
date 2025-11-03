<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\RatioValor;
use App\Models\User;

class Empresa extends Model
{
    use HasFactory;

    protected $fillable = [
        'rubro_id',
        'codigo',
        'nombre',
        'descripcion',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    /**
     * La empresa pertenece a un rubro (N:1).
     */
    public function rubro(): BelongsTo
    {
        return $this->belongsTo(Rubro::class);
    }

    /**
     * La empresa tiene su propio catálogo de cuentas (1:N).
     */
    public function catalogoCuentas(): HasMany
    {
        return $this->hasMany(CatalogoCuenta::class);
    }

    /**
     * La empresa tiene varios estados financieros por periodo (1:N).
     */
    public function estados(): HasMany
    {
        return $this->hasMany(Estado::class);
    }

    /**
     * La empresa tiene un registro de ventas mensuales (1:N).
     */
    public function ventasMensuales(): HasMany
    {
        return $this->hasMany(VentaMensual::class);
    }

    /**
     * La empresa tiene valores de ratios calculados por periodo (1:N).
     */
    public function ratiosValores(): HasMany
    {
        return $this->hasMany(RatioValor::class);
    }

    /**
     * La empresa tiene varios usuarios asignados (1:N).
     * Un usuario puede pertenecer a una empresa.
     */
    public function usuarios(): HasMany
    {
        return $this->hasMany(User::class);
    }
    
    // (Relación de seguridad omitida: accesoUsuarios)
}