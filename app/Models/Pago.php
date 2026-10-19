<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pago extends Model
{
    use HasFactory;

    protected $table = 'pagos';
    protected $guarded = [];

    protected $casts = [
        'monto' => 'decimal:2',
        'tasa_momento' => 'decimal:4',
        'anulado_en' => 'datetime'
    ];

    // ============================================================
    // RELACIONES
    // ============================================================

    public function caja(): BelongsTo
    {
        return $this->belongsTo(Caja::class, 'caja_id');
    }

    public function cita(): BelongsTo
    {
        return $this->belongsTo(Cita::class, 'cita_id');
    }

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class, 'paciente_id');
    }

    public function tasa(): BelongsTo
    {
        return $this->belongsTo(Tasa::class, 'tasa_id');
    }

    public function cambio(): BelongsTo
    {
        return $this->belongsTo(Cambio::class, 'cambio_id');
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }

    public function anuladoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'anulado_por');
    }

    // ============================================================
    // ACCESSORS DE CONVERSIÓN
    // ============================================================

    /**
     * Monto del pago en Bolívares.
     *
     * - bs    → monto (ya está en Bs)
     * - dolar → monto * tasa_momento
     * - euro  → monto * tasa_momento
     */
    public function getMontoBsAttribute(): float
    {
        return match ($this->moneda) {
            'bs'    => (float) $this->monto,
            'dolar', 'euro' => round((float) $this->monto * (float) $this->tasa_momento, 2),
            default => 0,
        };
    }

    /**
     * Monto del pago convertido a USD.
     *
     * - dolar → monto (ya está en USD)
     * - bs    → monto / tasa_dolar_del_momento
     * - euro  → (monto * tasa_euro_del_momento) / tasa_dolar_del_momento
     */
    public function getMontoUsdAttribute(): float
    {
        if ($this->moneda === 'dolar') {
            return (float) $this->monto;
        }

        $tasaDolar = $this->tasaDolarDelMomento();

        if ($tasaDolar <= 0) return 0;

        if ($this->moneda === 'bs') {
            return round((float) $this->monto / $tasaDolar, 2);
        }

        if ($this->moneda === 'euro') {
            $montoBs = (float) $this->monto * (float) $this->tasa_momento;
            return round($montoBs / $tasaDolar, 2);
        }

        return 0;
    }

    /**
     * Obtiene la tasa dólar vigente en el momento del pago.
     * Se busca en la misma fuente (tasa_id) porque asumimos que
     * todas las monedas de una tasa se publican juntas.
     */
    private function tasaDolarDelMomento(): float
    {
        return (float) Cambio::where('tasa_id', $this->tasa_id)
            ->where('moneda', 'dolar')
            ->where('created_at', '<=', $this->created_at)
            ->latest('created_at')
            ->value('monto') ?? 0;
    }

    // ============================================================
    // ACCESSORS DE ETIQUETAS
    // ============================================================

    public function getMonedaEtiquetaAttribute(): string
    {
        return match ($this->moneda) {
            'dolar' => 'USD',
            'euro'  => 'EUR',
            'bs'    => 'Bs',
            default => strtoupper($this->moneda),
        };
    }

    public function getFormaPagoEtiquetaAttribute(): string
    {
        return Caja::etiquetaFormaPago($this->forma_pago);
    }

    public function getMontoFormateadoAttribute(): string
    {
        $simbolo = match ($this->moneda) {
            'dolar' => '$',
            'euro'  => '€',
            'bs'    => 'Bs ',
            default => '',
        };

        return $simbolo . number_format((float) $this->monto, 2);
    }
    
    public function getEstaAnuladoAttribute(): bool
    {
        return $this->estado === 'ANULADO';
    }

    public function getEstaActivoAttribute(): bool
    {
        return $this->estado === 'ACTIVO';
    }

    // scopees 

    public function scopeActivos($query)
    {
        return $query->where('estado', 'ACTIVO');
    }

    public function scopeAnulados($query)
    {
        return $query->where('estado', 'ANULADO');
    }
}