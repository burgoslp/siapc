<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Cita extends Model
{
    use HasFactory;

    protected $table = 'citas';
    protected $guarded = [];

    protected $casts = [
        'fecha' => 'datetime',
        'total_pagado' => 'decimal:2',
    ];

    // ============================================================
    // RELACIONES
    // ============================================================

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

    public function pagos(): HasMany
    {
        return $this->hasMany(Pago::class, 'cita_id');
    }

    // ============================================================
    // SCOPES
    // ============================================================

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

    // ============================================================
    // ACCESSORS DE TOTALES
    // ============================================================

    /**
     * Total de la cita en Bs (usando la tasa congelada en la cita).
     */
    public function getTotalBsAttribute(): ?float
    {
        if (!$this->total || !$this->monto_tasa_cambio) {
            return null;
        }

        return round((float) $this->total * (float) $this->monto_tasa_cambio, 2);
    }

    /**
     * Total pagado en USD, calculado sumando los pagos.
     * Solo se usa para saber si la cita está PAGADA / PARCIAL.
     */
    public function getTotalPagadoUsdAttribute(): float
    {
        return round(
            $this->pagos->where('estado', 'ACTIVO')->sum(fn($p) => $p->monto_usd),
            2
        );
    }

    /**
     * Saldo pendiente en USD.
     */
    public function getSaldoPendienteUsdAttribute(): float
    {
        return max(0, round((float) $this->total - $this->total_pagado_usd, 2));
    }

    /**
     * Saldo pendiente en Bs (usando la tasa de la cita).
     */
    public function getSaldoPendienteBsAttribute(): float
    {
        $tasaCita = (float) $this->monto_tasa_cambio;
        if ($tasaCita <= 0) return 0;

        return round($this->saldo_pendiente_usd * $tasaCita, 2);
    }

    // ============================================================
    // ACCESSORS DE FORMATO
    // ============================================================

    public function getTotalFormateadoAttribute(): ?string
    {
        if (!$this->total) return null;
        return '$' . number_format((float) $this->total, 2);
    }

    public function getTotalBsFormateadoAttribute(): ?string
    {
        return $this->total_bs
            ? 'Bs ' . number_format($this->total_bs, 2)
            : null;
    }

    public function getTotalPagadoFormateadoAttribute(): string
    {
        return 'Bs ' . number_format((float) $this->total_pagado, 2);
    }

    public function getTotalPagadoUsdFormateadoAttribute(): string
    {
        return '$' . number_format($this->total_pagado_usd, 2);
    }

    public function getSaldoPendienteUsdFormateadoAttribute(): string
    {
        return '$' . number_format($this->saldo_pendiente_usd, 2);
    }

    // ============================================================
    // LÓGICA DE PAGOS
    // ============================================================

    /**
     * Recalcula el estado de pago según el acumulado en pagos.
     */
    public function recalcularEstadoPago(): void
    {
        $total = (float) $this->total;
        $pagadoUsd = $this->total_pagado_usd;

        if ($pagadoUsd >= $total) {
            $this->estado_pago = 'PAGADA';
        } elseif ($pagadoUsd > 0) {
            $this->estado_pago = 'PARCIAL';
        } else {
            $this->estado_pago = 'PENDIENTE';
        }

        $this->save();
    }

    /**
     * Agrega un monto en Bs al acumulador de la cita.
     * Este acumulador es para el cuadre de caja.
     */
    public function agregarPagoBs(float $montoBs): void
    {
        $this->total_pagado = round((float) $this->total_pagado + $montoBs, 2);
        $this->save();
    }

    // ============================================================
    // CONFLICTOS DE HORARIO
    // ============================================================

    public static function hayConflicto(
        int $medicoId,
        string $fecha,
        ?int $excluirCitaId = null
    ): bool {
        $inicio = \Carbon\Carbon::parse($fecha);
        $fin = $inicio->copy()->addMinutes(30);

        return static::where('medico_id', $medicoId)
            ->whereNotIn('estado', ['CANCELADA', 'NO_ASISTIO'])
            ->when($excluirCitaId, fn($q) => $q->where('id', '!=', $excluirCitaId))
            ->where(function ($q) use ($inicio, $fin) {
                $q->where('fecha', '<', $fin)
                  ->whereRaw('DATE_ADD(fecha, INTERVAL 30 MINUTE) > ?', [$inicio]);
            })
            ->exists();
    }
}