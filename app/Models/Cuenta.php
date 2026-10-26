<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cuenta extends Model
{
    use HasFactory;

    protected $table = 'cuentas';
    protected $guarded = [];

    protected $casts = [
        'saldo_inicial' => 'decimal:2',
        'activa' => 'boolean',
        'es_predeterminada' => 'boolean',
    ];

    // ---------- Relaciones ----------

    public function banco(): BelongsTo
    {
        return $this->belongsTo(Banco::class, 'banco_id');
    }

    public function comisiones(): HasMany
    {
        return $this->hasMany(Comision::class, 'cuenta_id');
    }

    public function movimientos(): HasMany
    {
        return $this->hasMany(MovimientoCuenta::class, 'cuenta_id');
    }

    public function movimientosEntreCuentasOrigen(): HasMany
    {
        return $this->hasMany(MovimientoEntreCuenta::class, 'cuenta_origen_id');
    }

    public function movimientosEntreCuentasDestino(): HasMany
    {
        return $this->hasMany(MovimientoEntreCuenta::class, 'cuenta_destino_id');
    }

    // ---------- Scopes ----------

    public function scopeActivas($query)
    {
        return $query->where('activa', true);
    }

    // ---------- Helpers ----------

    public static function predeterminada(): ?self
    {
        return static::where('es_predeterminada', true)
            ->where('activa', true)
            ->first();
    }

    public function comisionPara(string $formaPago): ?Comision
    {
        return $this->comisiones()
            ->where('forma_pago', $formaPago)
            ->where('activo', true)
            ->first();
    }

    // ---------- Accessors ----------

    /**
     * Saldo actual = último movimiento (saldo_despues) o saldo_inicial si no hay movimientos.
     */
    public function getSaldoActualAttribute(): float
    {
        $ultimo = $this->movimientos()
            ->latest('fecha')
            ->latest('id')
            ->first();

        return $ultimo ? (float) $ultimo->saldo_despues : (float) $this->saldo_inicial;
    }

    public function getSaldoFormateadoAttribute(): string
    {
        $simbolo = match ($this->moneda) {
            'dolar' => '$',
            'euro'  => '€',
            'bs'    => 'Bs ',
            default => '',
        };
        return $simbolo . number_format($this->saldo_actual, 2);
    }

    public function getTipoEtiquetaAttribute(): string
    {
        return match ($this->tipo) {
            'EFECTIVO' => 'Efectivo',
            'BANCO'    => 'Banco',
            'PLATAFORMA DIGITAL'   => 'Plataforma Digital',
            default    => $this->tipo,
        };
    }

    public function getMonedaEtiquetaAttribute(): string
    {
        return match ($this->moneda) {
            'dolar' => 'USD',
            'euro'  => 'EUR',
            'bs'    => 'Bs',
            default => strtoupper($this->moneda),
        };
    }
}