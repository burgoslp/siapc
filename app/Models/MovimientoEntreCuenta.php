<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MovimientoEntreCuenta extends Model
{
    use HasFactory;

    protected $table = 'movimientos_entre_cuentas';
    protected $guarded = [];

    protected $casts = [
        'monto_origen' => 'decimal:2',
        'monto_destino' => 'decimal:2',
        'tasa_momento' => 'decimal:4',
        'hay_cambio_moneda' => 'boolean',
        'tasa_personalizada' => 'boolean',
        'comision_origen_porcentaje' => 'decimal:2',
        'comision_origen_monto_fijo' => 'decimal:2',
        'comision_origen_monto' => 'decimal:2',
        'comision_destino_porcentaje' => 'decimal:2',
        'comision_destino_monto_fijo' => 'decimal:2',
        'comision_destino_monto' => 'decimal:2',
        'debito_total' => 'decimal:2',
        'credito_total' => 'decimal:2',
    ];

    // ---------- Relaciones ----------

    public function cuentaOrigen(): BelongsTo
    {
        return $this->belongsTo(Cuenta::class, 'cuenta_origen_id');
    }

    public function cuentaDestino(): BelongsTo
    {
        return $this->belongsTo(Cuenta::class, 'cuenta_destino_id');
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

    public function movimientos(): HasMany
    {
        return $this->hasMany(MovimientoCuenta::class, 'movimiento_entre_cuenta_id');
    }

    // ---------- Scopes ----------

    public function scopeRetiros($query)
    {
        return $query->where('tipo', 'RETIRO');
    }

    public function scopeDepositos($query)
    {
        return $query->where('tipo', 'DEPOSITO');
    }

    public function scopeTransferencias($query)
    {
        return $query->where('tipo', 'TRANSFERENCIA');
    }

    public function scopeConCambioMoneda($query)
    {
        return $query->where('hay_cambio_moneda', true);
    }

    // ---------- Accessors ----------

    public function getTipoEtiquetaAttribute(): string
    {
        return match ($this->tipo) {
            'RETIRO'        => 'Retiro',
            'DEPOSITO'      => 'Depósito',
            'TRANSFERENCIA' => 'Transferencia',
            default         => $this->tipo,
        };
    }

    public function getMonedaOrigenEtiquetaAttribute(): string
    {
        return match ($this->moneda_origen) {
            'dolar' => 'USD',
            'euro'  => 'EUR',
            'bs'    => 'Bs',
            default => strtoupper($this->moneda_origen),
        };
    }

    public function getMonedaDestinoEtiquetaAttribute(): string
    {
        return match ($this->moneda_destino) {
            'dolar' => 'USD',
            'euro'  => 'EUR',
            'bs'    => 'Bs',
            default => strtoupper($this->moneda_destino),
        };
    }

    public function getMontoOrigenFormateadoAttribute(): string
    {
        return $this->moneda_origen === 'bs'
            ? 'Bs ' . number_format((float) $this->monto_origen, 2)
            : ($this->moneda_origen === 'dolar' ? '$' : '€') . number_format((float) $this->monto_origen, 2);
    }

    public function getMontoDestinoFormateadoAttribute(): string
    {
        return $this->moneda_destino === 'bs'
            ? 'Bs ' . number_format((float) $this->monto_destino, 2)
            : ($this->moneda_destino === 'dolar' ? '$' : '€') . number_format((float) $this->monto_destino, 2);
    }

    public function getDebitoTotalFormateadoAttribute(): string
    {
        return $this->moneda_origen === 'bs'
            ? 'Bs ' . number_format((float) $this->debito_total, 2)
            : ($this->moneda_origen === 'dolar' ? '$' : '€') . number_format((float) $this->debito_total, 2);
    }

    public function getCreditoTotalFormateadoAttribute(): string
    {
        return $this->moneda_destino === 'bs'
            ? 'Bs ' . number_format((float) $this->credito_total, 2)
            : ($this->moneda_destino === 'dolar' ? '$' : '€') . number_format((float) $this->credito_total, 2);
    }
}