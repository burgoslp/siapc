<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Caja extends Model
{
    use HasFactory;

    protected $table = 'cajas';
    protected $guarded = [];

    protected $casts = [
        'abierta_en' => 'datetime',
        'cerrada_en' => 'datetime',
        'monto_inicial' => 'decimal:2',
        'total_efectivo' => 'decimal:2',
        'total_transferencia' => 'decimal:2',
        'total_pago_movil' => 'decimal:2',
        'total_tarjeta_debito' => 'decimal:2',
        'total_tarjeta_credito' => 'decimal:2',
        'total_zelle' => 'decimal:2',
        'total_binance' => 'decimal:2',
        'total_otros' => 'decimal:2',
        'total_general' => 'decimal:2',
    ];

    // ---------- Relaciones ----------

    public function abiertaPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'abierta_por');
    }

    public function cerradaPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cerrada_por');
    }

    public function pagos(): HasMany
    {
        return $this->hasMany(Pago::class, 'caja_id');
    }

    // ---------- Scopes ----------

    public function scopeAbierta($query)
    {
        return $query->where('estado', 'ABIERTA');
    }

    public function scopeCerrada($query)
    {
        return $query->where('estado', 'CERRADA');
    }

    // ---------- Helpers ----------

    /**
     * Obtiene la caja abierta actual (si existe).
     */
    public static function abierta(): ?self
    {
        return static::where('estado', 'ABIERTA')->latest('abierta_en')->first();
    }

    /**
     * ¿Hay una caja abierta ahora mismo?
     */
    public static function hayAbierta(): bool
    {
        return static::where('estado', 'ABIERTA')->exists();
    }

    /**
     * Suma el total en Bs de los pagos agrupados por forma de pago.
     * Retorna un array asociativo.
     */
    public function totalesPorFormaPago(): array
    {
        $formas = [
            'EFECTIVO',
            'TRANSFERENCIA',
            'PAGO_MOVIL',
            'TARJETA_DEBITO',
            'TARJETA_CREDITO',
            'ZELLE',
            'BINANCE',
            'OTRO',
        ];

        // Cargar todos los pagos UNA vez
        $pagos = $this->pagos()->get();

        $totales = [];

        foreach ($formas as $forma) {
            $total = 0;

            foreach ($pagos as $pago) {
                if ($pago->forma_pago === $forma) {
                    $total += (float) $pago->monto_bs;
                }
            }

            $totales[$forma] = round($total, 2);
        }

        return $totales;
    }

    /**
     * Total general en Bs (suma de todos los pagos de la caja).
     */
    public function totalGeneralBs(): float
    {
        return round(
            $this->pagos->sum(fn($p) => $p->monto_bs),
            2
        );
    }

    /**
     * Etiqueta legible para las formas de pago.
     */
    public static function etiquetaFormaPago(string $forma): string
    {
        return match ($forma) {
            'EFECTIVO'        => 'Efectivo',
            'TRANSFERENCIA'   => 'Transferencia',
            'PAGO_MOVIL'      => 'Pago Móvil',
            'TARJETA_DEBITO'  => 'Tarjeta Débito',
            'TARJETA_CREDITO' => 'Tarjeta Crédito',
            'ZELLE'           => 'Zelle',
            'BINANCE'         => 'Binance',
            'OTRO'            => 'Otro',
            default           => $forma,
        };
    }

    /**
     * Genera el siguiente código correlativo.
     * Ejemplo: CAJA-2026-10-15-001
     */
    public static function generarCodigo(): string
    {
        $prefijo = 'CAJA-' . now()->format('Y-m-d') . '-';

        $ultimo = static::where('codigo', 'like', $prefijo . '%')
            ->orderByDesc('codigo')
            ->value('codigo');

        $numero = 1;

        if ($ultimo) {
            $numero = (int) substr($ultimo, -3) + 1;
        }

        return $prefijo . str_pad($numero, 3, '0', STR_PAD_LEFT);
    }


    /**
     * ¿La caja fue abierta hoy?
     */
    public function esDeHoy(): bool
    {
        return $this->abierta_en->isToday();
    }

    /**
     * ¿La caja está vencida (abierta de un día anterior)?
     */
    public function estaVencida(): bool
    {
        return $this->estado === 'ABIERTA' && !$this->esDeHoy();
    }

    /**
     * Caja abierta actual (con datos del día).
     * Devuelve null si la caja abierta es de un día anterior.
     */
    public static function abiertaDeHoy(): ?self
    {
        $caja = static::abierta();

        if (!$caja || !$caja->esDeHoy()) {
            return null;
        }

        return $caja;
    }
}