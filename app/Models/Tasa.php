<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Tasa extends Model
{
    use HasFactory;

    protected $table = 'tasas';
    protected $guarded = [];

    protected $casts = [
        'predeterminado' => 'boolean',
        'activa' => 'boolean',
    ];

    // ---------- Relaciones ----------

    public function cambios(): HasMany
    {
        return $this->hasMany(Cambio::class, 'tasa_id');
    }

    public function citas(): HasMany
    {
        return $this->hasMany(Cita::class, 'tasa_id');
    }

    /**
     * Último cambio en dólares (el vigente).
     */
    public function cambioActual(): HasOne
    {
        return $this->hasOne(Cambio::class, 'tasa_id')
            ->where('moneda', 'dolar')
            ->latestOfMany('created_at');
    }

    /**
     * Último cambio para una moneda específica.
     */
    public function cambioActualDe(string $moneda = 'dolar'): ?Cambio
    {
        return $this->cambios()
            ->where('moneda', $moneda)
            ->latest('created_at')
            ->first();
    }

    /**
     * Monto vigente para una moneda.
     */
    public function montoActual(string $moneda = 'dolar'): ?float
    {
        $cambio = $this->cambioActualDe($moneda);
        return $cambio ? (float) $cambio->monto : null;
    }
    /**
     * Devuelve un array con las monedas activas y sus montos vigentes.
     * Ejemplo: ['dolar' => 36.50, 'euro' => 80.50]
     */
    public function montosVigentes(): array
    {
        $montos = [];

        foreach (['dolar', 'euro'] as $moneda) {
            $cambio = $this->cambioActualDe($moneda);
            if ($cambio) {
                $montos[$moneda] = (float) $cambio->monto;
            }
        }

        return $montos;
    }

    // ---------- Scopes ----------

    public function scopeActivas($query)
    {
        return $query->where('activa', true);
    }

    // ---------- Helpers estáticos ----------

    /**
     * Devuelve la tasa predeterminada (activa).
     */
    public static function predeterminada(): ?self
    {
        return static::where('predeterminado', true)
            ->where('activa', true)
            ->first();
    }

    /**
     * Monto de la tasa predeterminada actual.
     * Útil para mostrar "Tasa del día" en la UI.
     */
    public static function montoPredeterminada(string $moneda = 'dolar'): ?float
    {
        $tasa = static::predeterminada();
        return $tasa?->montoActual($moneda);
    }

    /**
     * Monto de una tasa por nombre (ej: 'bcv').
     */
    public static function montoDe(string $nombre, string $moneda = 'dolar'): ?float
    {
        $tasa = static::where('nombre', $nombre)->where('activa', true)->first();
        return $tasa?->montoActual($moneda);
    }

    /**
     * Monto de la tasa en una fecha pasada (para reportes).
     */
    public function montoEnFecha(string $moneda, \DateTimeInterface $fecha): ?float
    {
        $cambio = $this->cambios()
            ->where('moneda', $moneda)
            ->where('created_at', '<=', $fecha)
            ->latest('created_at')
            ->first();

        return $cambio ? (float) $cambio->monto : null;
    }
}