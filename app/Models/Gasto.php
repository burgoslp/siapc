<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Gasto extends Model
{
    use HasFactory;

    protected $table = 'gastos';
    protected $guarded = [];

    protected $casts = [
        'fecha_gasto' => 'date',
        'monto' => 'decimal:2',
        'tasa_cambio' => 'decimal:4', 
    ];

    public function cuenta(): BelongsTo
    {
        return $this->belongsTo(Cuenta::class, 'cuenta_id');
    }

    public function registrador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }

    public function recurrencia(): BelongsTo
    {
        return $this->belongsTo(Recurrencia::class, 'recurrencia_id');
    }
    
    //Un accesor para saber si es fijo o variable rápidamente
    public function getEsFijoAttribute(): bool
    {
        return $this->tipo_gasto === 'FIJO';
    }
}