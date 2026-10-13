<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cambio extends Model
{
    use HasFactory;

    protected $table = 'cambios';
    protected $guarded = [];

    protected $casts = [
        'monto' => 'decimal:4',
    ];

    // ---------- Relaciones ----------

    public function tasa(): BelongsTo
    {
        return $this->belongsTo(Tasa::class, 'tasa_id');
    }

    public function citas(): HasMany
    {
        return $this->hasMany(Cita::class, 'cambio_id');
    }

    // ---------- Helpers estáticos ----------

    /**
     * Último cambio registrado para una tasa y moneda.
     */
    public static function vigente(int $tasaId, string $moneda = 'dolar'): ?self
    {
        return static::where('tasa_id', $tasaId)
            ->where('moneda', $moneda)
            ->latest('created_at')
            ->first();
    }

    // ---------- Accessors ----------

    public function getMontoFormateadoAttribute(): string
    {
        return number_format($this->monto, 2);
    }

    public function getMonedaEtiquetaAttribute(): string
    {
        return match ($this->moneda) {
            'dolar' => 'USD',
            'euro'  => 'EUR',
            default => strtoupper($this->moneda),
        };
    }
}