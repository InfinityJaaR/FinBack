<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Estado extends Model
{
    use HasFactory;

    protected $fillable = [
        'empresa_id',
        'periodo_id',
        'tipo',
    ];

    /**
     * El estado pertenece a una empresa (N:1).
     */
    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    /**
     * El estado pertenece a un periodo (N:1).
     */
    public function periodo(): BelongsTo
    {
        return $this->belongsTo(Periodo::class);
    }

    /**
     * Un estado (Balance o Resultados) tiene muchas partidas/líneas de detalle (1:N).
     */
    public function detalles(): HasMany
    {
        return $this->hasMany(DetalleEstado::class);
    }
}