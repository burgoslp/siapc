<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tarifa extends Model
{
    use HasFactory;

    protected $table = 'tarifas';
    protected $guarded = [];

    protected $casts = [
        'precio_usd' => 'decimal:2',
        'activo' => 'boolean',
    ];

    // ---------- Relaciones ----------

    public function especialidad(): BelongsTo
    {
        return $this->belongsTo(Especialidad::class, 'especialidad_id');
    }

    public function citas(): HasMany
    {
        return $this->hasMany(Cita::class, 'tarifa_id');
    }

    // ---------- Scopes ----------

    public function scopeActivas($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Busca la tarifa aplicable para una especialidad y tipo de consulta.
     * Prioriza la específica sobre la genérica.
     */
    public static function paraEspecialidad(?int $especialidadId, string $tipo = 'CONSULTA'): ?self
    {
        return static::query()
            ->where('activo', true)
            ->where('tipo', $tipo)
            ->where(function ($q) use ($especialidadId) {
                if ($especialidadId) {
                    $q->where('especialidad_id', $especialidadId)
                      ->orWhereNull('especialidad_id');
                } else {
                    $q->whereNull('especialidad_id');
                }
            })
            ->orderByRaw('CASE WHEN especialidad_id IS NULL THEN 1 ELSE 0 END')
            ->first();
    }

    // ---------- Accessors ----------

    public function getEsGenericaAttribute(): bool
    {
        return $this->especialidad_id === null;
    }

    public function getPrecioFormateadoAttribute(): string
    {
        return '$' . number_format($this->precio_usd, 2);
    }

    public function getTipoEtiquetaAttribute(): string
    {
        return match ($this->tipo) {
            'CONSULTA'      => 'Consulta',
            'PROCEDIMIENTO' => 'Procedimiento',
            'EXAMEN'        => 'Examen',
            'URGENCIA'      => 'Urgencia',
            'SEGUIMIENTO'   => 'Seguimiento',
            default         => $this->tipo,
        };
    }
}