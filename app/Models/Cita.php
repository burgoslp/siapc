<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Cita extends Model
{
    use HasFactory;

    protected $table = 'citas';
    protected $guarded = [];

    protected $casts = [
        'fecha' => 'datetime',
    ];

    // ---------- Relaciones ----------

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class, 'paciente_id');
    }

    public function medico(): BelongsTo
    {
        return $this->belongsTo(Medico::class, 'medico_id');
    }

    public function consulta(): HasOne
    {
        return $this->hasOne(Consulta::class, 'cita_id');
    }
    public function tarifa(): BelongsTo
    {
        return $this->belongsTo(Tarifa::class, 'tarifa_id');
    }

    public function tasa(): BelongsTo
    {
        return $this->belongsTo(Tasa::class, 'tasa_id');
    }

    public function cambio(): BelongsTo
    {
        return $this->belongsTo(Cambio::class, 'cambio_id');
    }

    // ---------- Scopes ----------

    public function scopeHoy($q)
    {
        return $q->whereDate('fecha', today());
    }

    public function scopeFuturas($q)
    {
        return $q->where('fecha', '>=', now());
    }

    public function scopePendientes($q)
    {
        return $q->where('estado', 'PENDIENTE');
    }

    public function scopeConfirmadas($q)
    {
        return $q->where('estado', 'CONFIRMADA');
    }

    public function scopeCompletadas($q)
    {
        return $q->where('estado', 'COMPLETADA');
    }
    // ---------- Accessors de facturación ----------

    /**
     * Total en bolívares usando la tasa congelada en la cita.
     */
    public function getTotalBsAttribute(): ?float
    {
        if (!$this->total || !$this->monto_tasa_cambio) {
            return null;
        }

        return round($this->total * $this->monto_tasa_cambio, 2);
    }

    public function getTotalFormateadoAttribute(): ?string
    {
        if (!$this->total) return null;
        return '$' . number_format($this->total, 2);
    }

    public function getTotalBsFormateadoAttribute(): ?string
    {
        $totalBs = $this->total_bs;
        if (!$totalBs) return null;
        return 'Bs ' . number_format($totalBs, 2);
    }

    /**
     * Verifica si existe un conflicto de horario para un médico.
     * Ignora la cita actual al editar.
     */
    public static function hayConflicto(
        int $medicoId,
        string $fecha,
        ?int $excluirCitaId = null
    ): bool {
        // Consideramos una cita que dura 30 minutos por defecto
        $inicio = \Carbon\Carbon::parse($fecha);
        $fin = $inicio->copy()->addMinutes(30);

        return static::where('medico_id', $medicoId)
            ->where('estado', '!=', 'CANCELADA')
            ->when($excluirCitaId, fn($q) => $q->where('id', '!=', $excluirCitaId))
            ->where(function ($q) use ($inicio, $fin) {
                // Se solapa si: (nueva_inicio < existente_fin) AND (nueva_fin > existente_inicio)
                $q->where('fecha', '<', $fin)
                  ->whereRaw('DATE_ADD(fecha, INTERVAL 30 MINUTE) > ?', [$inicio]);
            })
            ->exists();
    }
}