<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Comision extends Model
{
    use HasFactory;

    protected $table = 'comisiones';
    protected $guarded = [];

    protected $casts = [
        'porcentaje_ingreso' => 'decimal:2',
        'monto_fijo_ingreso' => 'decimal:2',
        'porcentaje_egreso' => 'decimal:2',
        'monto_fijo_egreso' => 'decimal:2',
        'activo' => 'boolean',
    ];

    public function cuenta(): BelongsTo
    {
        return $this->belongsTo(Cuenta::class, 'cuenta_id');
    }

    public function scopeActivas($query)
    {
        return $query->where('activo', true);
    }

    // ---------- Cálculos ----------

    /**
     * Calcula la comisión de ingreso para un monto dado.
     */
    public function calcularComisionIngreso(float $monto): float
    {
        return round(($monto * (float) $this->porcentaje_ingreso / 100) + (float) $this->monto_fijo_ingreso, 2);
    }

    /**
     * Calcula la comisión de egreso para un monto dado.
     */
    public function calcularComisionEgreso(float $monto): float
    {
        return round(($monto * (float) $this->porcentaje_egreso / 100) + (float) $this->monto_fijo_egreso, 2);
    }

    // ---------- Accessors ----------

    public function getFormaPagoEtiquetaAttribute(): string
    {
        return match ($this->forma_pago) {
            'EFECTIVO'        => 'Efectivo',
            'TRANSFERENCIA'   => 'Transferencia',
            'PAGO_MOVIL'      => 'Pago Móvil',
            'TARJETA_DEBITO'  => 'Tarjeta Débito',
            'TARJETA_CREDITO' => 'Tarjeta Crédito',
            'ZELLE'           => 'Zelle',
            'BINANCE'         => 'Binance',
            'OTRO'            => 'Otro',
            default           => $this->forma_pago,
        };
    }

    public function getDescripcionIngresoAttribute(): string
    {
        if ($this->porcentaje_ingreso > 0 && $this->monto_fijo_ingreso > 0) {
            return "{$this->porcentaje_ingreso}% + $" . number_format($this->monto_fijo_ingreso, 2);
        }
        if ($this->porcentaje_ingreso > 0) {
            return "{$this->porcentaje_ingreso}%";
        }
        if ($this->monto_fijo_ingreso > 0) {
            return '$' . number_format($this->monto_fijo_ingreso, 2);
        }
        return 'Sin comisión';
    }

    public function getDescripcionEgresoAttribute(): string
    {
        if ($this->porcentaje_egreso > 0 && $this->monto_fijo_egreso > 0) {
            return "{$this->porcentaje_egreso}% + $" . number_format($this->monto_fijo_egreso, 2);
        }
        if ($this->porcentaje_egreso > 0) {
            return "{$this->porcentaje_egreso}%";
        }
        if ($this->monto_fijo_egreso > 0) {
            return '$' . number_format($this->monto_fijo_egreso, 2);
        }
        return 'Sin comisión';
    }
}