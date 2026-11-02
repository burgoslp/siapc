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
        'cambio' => 'decimal:4', // 👈 Ahora se llama 'cambio'
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

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }

    public function anuladoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'anulado_por');
    }

    public function cuenta(): BelongsTo
    {
        return $this->belongsTo(Cuenta::class, 'cuenta_id');
    }

    // ============================================================
    // ACCESSORS DE CONVERSIÓN
    // ============================================================

    /**
     * Monto del pago en Bolívares.
     *
     * - bs    → monto (ya está en Bs)
     * - dolar → monto * cambio
     * - euro  → monto * cambio
     */
    public function getMontoBsAttribute(): float
    {
        return match ($this->moneda) {
            'bs'    => (float) $this->monto,
            'dolar', 'euro' => round((float) $this->monto * (float) $this->cambio, 2),
            default => 0,
        };
    }

    /**
     * Monto del pago convertido a USD.
     *
     * - dolar → monto (ya está en USD)
     * - bs    → monto / cambio
     * - euro  → monto / cambio (asumiendo que cambio es la del euro)
     */
    public function getMontoUsdAttribute(): float
    {
        $monto = (float) $this->monto;
        $tasa = (float) $this->cambio; 

        // Si el pago ya está en dólares, no hay conversión
        if ($this->moneda === 'dolar') {
            return $monto;
        }

        // Si la tasa es 0, no se puede convertir
        if ($tasa <= 0) {
            return 0;
        }

        // Si es en Bolívares, dividimos entre la tasa
        if ($this->moneda === 'bs') {
            return round($monto / $tasa, 2);
        }

        // Si es en Euros, asumimos que el cambio guardado es el del euro
        if ($this->moneda === 'euro') {
            return round($monto, 2);
        }

        return 0;
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

    /**
     * Muestra el nombre de la tasa aplicada (BCV, Paralelo, etc.)
     */
    public function getTasaEtiquetaAttribute(): string
    {
        return $this->tasa ?? 'N/A';
    }

    public function getEstaAnuladoAttribute(): bool
    {
        return $this->estado === 'ANULADO';
    }

    public function getEstaActivoAttribute(): bool
    {
        return $this->estado === 'ACTIVO';
    }

    // ============================================================
    // SCOPES
    // ============================================================

    public function scopeActivos($query)
    {
        return $query->where('estado', 'ACTIVO');
    }

    public function scopeAnulados($query)
    {
        return $query->where('estado', 'ANULADO');
    }
}