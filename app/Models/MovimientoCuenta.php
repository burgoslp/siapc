<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovimientoCuenta extends Model
{
    use HasFactory;

    protected $table = 'movimientos_cuenta';
    protected $guarded = [];

    protected $casts = [
        'monto' => 'decimal:2',
        'saldo_despues' => 'decimal:2',
        'fecha' => 'datetime',
    ];

    // ---------- Relaciones ----------

    public function cuenta(): BelongsTo
    {
        return $this->belongsTo(Cuenta::class, 'cuenta_id');
    }

    public function movimientoEntreCuenta(): BelongsTo
    {
        return $this->belongsTo(MovimientoEntreCuenta::class, 'movimiento_entre_cuenta_id');
    }

    // ---------- Scopes ----------

    public function scopeIngresos($query)
    {
        return $query->where('tipo', 'INGRESO');
    }

    public function scopeEgresos($query)
    {
        return $query->where('tipo', 'EGRESO');
    }

    // ---------- Accessors ----------

    public function getTipoEtiquetaAttribute(): string
    {
        return $this->tipo === 'INGRESO' ? 'Ingreso' : 'Egreso';
    }

    public function getOrigenEtiquetaAttribute(): string
    {
        return match ($this->origen_tipo) {
            'PAGO'           => 'Pago',
            'GASTO'          => 'Gasto',
            'TRANSFERENCIA'  => 'Transferencia',
            'CAMBIO_MONEDA'  => 'Cambio de moneda',
            'AJUSTE'         => 'Ajuste',
            default          => $this->origen_tipo,
        };
    }

    public function getMontoFormateadoAttribute(): string
    {
        if (!$this->cuenta) return number_format((float) $this->monto, 2);

        $signo = $this->tipo === 'EGRESO' ? '-' : '+';

        return match ($this->cuenta->moneda) {
            'dolar' => $signo . '$' . number_format((float) $this->monto, 2),
            'euro'  => $signo . '€' . number_format((float) $this->monto, 2),
            'bs'    => $signo . 'Bs ' . number_format((float) $this->monto, 2),
            default => $signo . number_format((float) $this->monto, 2),
        };
    }

    // ---------- Helper estático ----------

    /**
     * Registra un movimiento y actualiza el saldo.
     *
     * @param int $cuentaId
     * @param string $tipo INGRESO | EGRESO
     * @param string $origenTipo PAGO | GASTO | TRANSFERENCIA | CAMBIO_MONEDA | AJUSTE
     * @param int|null $origenId
     * @param int|null $movimientoEntreCuentaId
     * @param float $monto (en la moneda de la cuenta)
     * @param string|null $descripcion
     * @return self
     */
    public static function registrar(
        int $cuentaId,
        string $tipo,
        string $origenTipo,
        ?int $origenId,
        ?int $movimientoEntreCuentaId,
        float $monto,
        ?string $descripcion = null
    ): self {
        $cuenta = Cuenta::find($cuentaId);

        if (!$cuenta) {
            throw new \Exception("Cuenta no encontrada: {$cuentaId}");
        }

        // Calcular saldo actual
        $saldoActual = $cuenta->saldo_actual;

        $nuevoSaldo = $tipo === 'INGRESO'
            ? $saldoActual + $monto
            : $saldoActual - $monto;

        return static::create([
            'cuenta_id' => $cuentaId,
            'tipo' => $tipo,
            'origen_tipo' => $origenTipo,
            'origen_id' => $origenId,
            'movimiento_entre_cuenta_id' => $movimientoEntreCuentaId,
            'monto' => $monto,
            'saldo_despues' => round($nuevoSaldo, 2),
            'fecha' => now(),
            'descripcion' => $descripcion,
        ]);
    }
}